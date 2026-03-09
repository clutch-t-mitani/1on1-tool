# 日次ログ入力機能 詳細設計

## 1. 機能概要

部下が4つの質問に対して音声またはテキストで回答し、DBに暗号化保存する。
保存後は本人も内容を再取得できない（非可逆性の担保）。

## 2. 日付変更線の定義

「今日分」として許容する時間帯を以下のように定義する。

```
当日 00:00 〜 翌日 11:59:59
```

システム上の「現在の日付」は `SystemSetting::getMasterDate()` から取得する。管理者が日付を操作している場合はその値を優先する。

```php
// 日付変更線の判定ロジック（UseCaseに実装）
$masterNow = SystemSetting::getMasterDate();
$todayBase = $masterNow->hour < 12
    ? $masterNow->startOfDay()
    : $masterNow->copy()->addDay()->startOfDay();
$targetDate = $todayBase->toDateString(); // daily_log の対象日
```

## 3. テキスト入力フロー

```
Vue.js → POST /api/daily-logs/text
    → StoreTextLogRequest（バリデーション）
    → StoreTextLogUseCase
        → 重複チェック（同ユーザー・同質問・同日）
        → EncryptionService::encrypt($answerText)
        → DailyLog::create()
    → 201レスポンス
```

### StoreTextLogInput（DTO）

```php
final readonly class StoreTextLogInput
{
    public function __construct(
        public int $userId,
        public int $questionId,
        public string $answerText,
        public string $targetDate,  // "2026-03-04"
    ) {}
}
```

### StoreTextLogUseCase

```php
final class StoreTextLogUseCase
{
    public function __construct(
        private readonly EncryptionService $encryption,
    ) {}

    public function execute(StoreTextLogInput $input): void
    {
        // 重複チェック
        $alreadyAnswered = DailyLog::query()
            ->forUser($input->userId)
            ->where('question_id', $input->questionId)
            ->whereDate('created_at', $input->targetDate)
            ->exists();

        if ($alreadyAnswered) {
            throw new DuplicateLogException();
        }

        DailyLog::create([
            'user_id'     => $input->userId,
            'question_id' => $input->questionId,
            'answer_text' => $this->encryption->encrypt($input->answerText),
        ]);
    }
}
```

## 4. 音声入力フロー

```
Vue.js → POST /api/daily-logs/voice（音声ファイル）
    → StoreVoiceLogRequest（バリデーション）
    → StoreVoiceLogUseCase
        → S3へ一時保存（temp/audio/{uuid}.webm）
        → ProcessVoiceLogJob をキューに投入
    → 202レスポンス（即時返却）

--- 非同期（ProcessVoiceLogJob） ---
    → WhisperService::transcribe(s3Path)
    → EncryptionService::encrypt(transcribedText)
    → DailyLog::create()
    → S3から音声ファイルを物理削除
```

### ProcessVoiceLogJob

```php
final class ProcessVoiceLogJob implements ShouldQueue
{
    public int $timeout = 120;
    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
        public readonly int $questionId,
        public readonly string $s3Path,
        public readonly string $targetDate,
    ) {}

    public function handle(
        WhisperService $whisper,
        EncryptionService $encryption,
    ): void {
        $transcribed = $whisper->transcribe($this->s3Path);

        DailyLog::create([
            'user_id'     => $this->userId,
            'question_id' => $this->questionId,
            'answer_text' => $encryption->encrypt($transcribed),
        ]);

        Storage::disk('s3')->delete($this->s3Path);
    }
}
```

## 5. 入力状況取得フロー

```
GET /api/daily-logs/status
    → DailyLogController::status()
    → 当日分のDailyLogを question_id で取得
    → 回答済みのquestion_idリスト + 総入力日数を返す
```

**総入力日数の定義:** `daily_logs` テーブルで `user_id` が一致するレコードの `created_at` の日付の種類数（重複排除）。

## 6. バリデーション仕様

### StoreTextLogRequest

| フィールド | ルール |
|---|---|
| `question_id` | 必須、整数、`questions` テーブルに存在かつ `is_active = true` |
| `answer_text` | 必須、文字列、1文字以上・2000文字以内 |

### StoreVoiceLogRequest

| フィールド | ルール |
|---|---|
| `question_id` | 必須、整数、`questions` テーブルに存在かつ `is_active = true` |
| `audio` | 必須、ファイル、MIMEタイプ: `audio/webm`, `audio/mp4`, `audio/wav`、最大25MB |

## 7. エラーケース

| 状況 | HTTPステータス | メッセージ |
|---|---|---|
| 同日・同質問に既に回答済み | 422 | 「本日分は既に入力済みです。」 |
| Whisperの変換に失敗 | Jobリトライ後失敗時にログ記録、ユーザーへの通知なし |
