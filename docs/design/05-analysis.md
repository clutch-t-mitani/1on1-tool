# AI要約生成・注釈・公開機能 詳細設計

## 1. 機能概要

5日分以上の**未使用ログ**（前回の要約以降に入力されたログ）が蓄積された部下が「解析実行」を押すと、バックグラウンドでGPT-4oが感情傾向を要約する。要約に使用したログには `summarized_at` を記録し、次回の要約対象から除外する。要約完了後、部下が注釈を加えて上司に公開する。

## 2. 要約生成フロー

```
Vue.js → POST /api/analyses
    → GenerateAnalysisUseCase
        → ログ件数チェック（5日分以上）
        → analyses レコードを pending 状態で作成
        → GenerateAnalysisJob をキューに投入（ai-processing キュー）
    → 202レスポンス（analysis_id を返す）

--- 非同期（GenerateAnalysisJob） ---
    → 対象期間のDailyLogを取得（暗号化済み）
    → EncryptionService::decrypt() で一括復号（メモリ上のみ）
    → GptSummaryService::summarize(decryptedLogs)
    → analyses.summary_content に保存
    → AnalysisCompleted イベントを発火
        → BroadcastAnalysisCompleted リスナー
            → Reverb 経由で Vue.js へ WebSocket 通知
```

### GenerateAnalysisInput（DTO）

```php
final readonly class GenerateAnalysisInput
{
    public function __construct(
        public int $userId,
    ) {}
}
```

### GenerateAnalysisUseCase

```php
final class GenerateAnalysisUseCase
{
    public function execute(GenerateAnalysisInput $input): Analysis
    {
        // 未使用ログが5日分以上あるかチェック
        $logDays = DailyLog::query()
            ->forUser($input->userId)
            ->notSummarized()
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->count();

        if ($logDays < 5) {
            throw new InsufficientLogsException();
        }

        // 処理中チェック
        $inProgress = Analysis::query()
            ->where('user_id', $input->userId)
            ->whereNull('summary_content')
            ->whereNull('deleted_at')
            ->exists();

        if ($inProgress) {
            throw new AnalysisAlreadyInProgressException();
        }

        $analysis = Analysis::create([
            'user_id'         => $input->userId,
            'viewer_id'       => null,  // 公開時に設定
            'summary_content' => null,  // Job完了後に更新
        ]);

        GenerateAnalysisJob::dispatch($analysis->id, $input->userId);

        return $analysis;
    }
}
```

### GenerateAnalysisJob

```php
final class GenerateAnalysisJob implements ShouldQueue
{
    public string $queue = 'ai-processing';
    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(
        public readonly int $analysisId,
        public readonly int $userId,
    ) {}

    public function handle(
        EncryptionService $encryption,
        GptSummaryService $gpt,
    ): void {
        // 未使用ログのみ取得
        $logs = DailyLog::query()
            ->forUser($this->userId)
            ->notSummarized()
            ->with('question')
            ->get();

        // メモリ上でのみ復号
        $decryptedLogs = $logs->map(fn (DailyLog $log) => [
            'question' => $log->question->content,
            'answer'   => $encryption->decrypt($log->answer_text),
            'date'     => $log->created_at->toDateString(),
        ])->toArray();

        $summary = $gpt->summarize($decryptedLogs);

        Analysis::find($this->analysisId)->update([
            'summary_content' => $summary,
        ]);

        // 使用したログに summarized_at を記録
        DailyLog::query()
            ->whereIn('id', $logs->pluck('id'))
            ->update(['summarized_at' => now()]);

        event(new AnalysisCompleted($this->analysisId, $this->userId));
    }
}
```

## 3. GPTプロンプト設計

### システムプロンプト

```
あなたは部下の専属エージェント（通訳者）です。
以下のルールを厳守してください。

- 部下の感情・本音を全肯定する立場で解釈する
- 上司が「改善のヒント」として受け取れる前向きな表現に変換する
- 主語を「個人」ではなく「仕組みや環境」に置き換える
- 感情の傾向・継続テーマ・温度感の変化を3〜5点でまとめる
- 1点あたり100〜200字程度で記述する
- 評価・判断・アドバイスは行わない
```

### ユーザープロンプト（動的生成）

```
以下は{name}さんの過去{n}日間の日々の記録です。

{date}: {question} → {answer}
{date}: {question} → {answer}
...

この内容を元に、感情の傾向を要約してください。
```

### レスポンス形式（JSON）

```json
{
    "points": [
        {
            "theme": "達成感と自己効力感の高まり",
            "content": "設計レビューや新機能の実装において..."
        },
        {
            "theme": "コミュニケーション面での負荷",
            "content": "チーム内での情報共有プロセスに..."
        }
    ]
}
```

`summary_content` カラムにはJSONをそのまま文字列で保存する。

## 4. 注釈保存フロー

```
Vue.js → PATCH /api/analyses/{id}/annotation
    → StoreAnnotationRequest（バリデーション）
    → SaveAnnotationUseCase
        → 所有者チェック（user_id === 認証ユーザー）
        → 公開前チェック（published_at === null）
        → analyses.annotation_text を更新
    → 200レスポンス
```

### バリデーション

| フィールド | ルール |
|---|---|
| `annotation_text` | null可、文字列、200文字以内 |

## 5. 公開フロー

```
Vue.js → POST /api/analyses/{id}/publish
    → PublishAnalysisRequest（バリデーション）
    → PublishAnalysisUseCase
        → 所有者チェック
        → 公開前チェック（published_at === null）
        → viewer_id の同一会社チェック
        → analyses.viewer_id, published_at を更新
    → 200レスポンス
```

### PublishAnalysisInput（DTO）

```php
final readonly class PublishAnalysisInput
{
    public function __construct(
        public int $analysisId,
        public int $userId,
        public int $viewerId,
    ) {}
}
```

## 6. 上司閲覧フロー

```
Vue.js → GET /api/viewer/analyses
    → Analysis::query()
        ->published()
        ->visibleToViewer($viewerId)
        ->notExpired()
        ->with('user')
        ->get()
    → AnalysisSummaryResource でレスポンス整形
```

### 有効期限の定義

`published_at + 168時間（7日）` を超えると `scopeNotExpired` で除外される。

```php
public function scopeNotExpired(Builder $query): void
{
    $query->where('published_at', '>=', now()->subDays(7));
}
```

## 7. WebSocket通知

Reverb のプライベートチャンネルを使用する。

```
チャンネル名: private-analysis.{userId}
イベント名:   AnalysisCompleted
ペイロード:   { "analysis_id": 5 }
```

Vue.js側はこのイベントを受信したタイミングで `GET /api/analyses/{id}` を呼び出して結果を取得し、画面を「注釈入力モード」へ切り替える。

## 8. エラーケース

| 状況 | HTTPステータス | メッセージ |
|---|---|---|
| ログが5日分未満 | 422 | 「あと{n}日分の入力が必要です。」 |
| 処理中のJobが既に存在 | 422 | 「解析を実行中です。完了までお待ちください。」 |
| 公開済みの要約に注釈を保存しようとした | 422 | 「公開済みのため変更できません。」 |
| viewer_id が別会社のユーザー | 422 | 「指定されたユーザーは選択できません。」 |
| 7日を超えた要約を上司が閲覧しようとした | 403 | 「閲覧期限が過ぎています。」 |
