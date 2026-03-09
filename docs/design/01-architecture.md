# アプリケーション構造・クラス設計

## 1. ディレクトリ構成

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── DailyLogController.php
│   │   ├── AnalysisController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── UserController.php
│   │       ├── CompanyController.php
│   │       ├── DailyLogController.php
│   │       ├── AnalysisController.php
│   │       └── SystemSettingController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   └── LoginRequest.php
│   │   ├── DailyLog/
│   │   │   ├── StoreTextLogRequest.php
│   │   │   └── StoreVoiceLogRequest.php
│   │   ├── Analysis/
│   │   │   ├── StoreAnnotationRequest.php
│   │   │   └── PublishAnalysisRequest.php
│   │   └── Admin/
│   │       ├── StoreUserRequest.php
│   │       ├── UpdateUserRequest.php
│   │       └── UpdateSystemDateRequest.php
│   └── Resources/
│       ├── DailyLogResource.php
│       ├── AnalysisResource.php
│       ├── AnalysisSummaryResource.php
│       └── UserResource.php
├── UseCases/
│   ├── Auth/
│   │   └── LoginUseCase.php
│   ├── DailyLog/
│   │   ├── StoreTextLogUseCase.php
│   │   └── StoreVoiceLogUseCase.php
│   ├── Analysis/
│   │   ├── GenerateAnalysisUseCase.php
│   │   ├── SaveAnnotationUseCase.php
│   │   └── PublishAnalysisUseCase.php
│   └── Admin/
│       ├── UpdateSystemDateUseCase.php
│       ├── CreateUserUseCase.php
│       └── UpdateUserUseCase.php
├── DTOs/
│   ├── DailyLog/
│   │   ├── StoreTextLogInput.php
│   │   └── StoreVoiceLogInput.php
│   ├── Analysis/
│   │   ├── GenerateAnalysisInput.php
│   │   ├── SaveAnnotationInput.php
│   │   └── PublishAnalysisInput.php
│   └── Admin/
│       ├── CreateUserInput.php
│       └── UpdateSystemDateInput.php
├── Jobs/
│   ├── ProcessVoiceLogJob.php
│   └── GenerateAnalysisJob.php
├── Events/
│   └── AnalysisCompleted.php
├── Listeners/
│   └── BroadcastAnalysisCompleted.php
├── Models/
│   ├── Company.php
│   ├── User.php
│   ├── Question.php
│   ├── DailyLog.php
│   ├── Analysis.php
│   └── SystemSetting.php
└── Services/
    ├── EncryptionService.php
    ├── WhisperService.php
    └── GptSummaryService.php
```

## 2. レイヤー責務

| レイヤー | 責務 | 禁止事項 |
|---|---|---|
| Controller | HTTPリクエストの受け取り・バリデーション済みデータをUseCaseへ渡す・レスポンス返却 | ビジネスロジック、DB操作 |
| UseCase | 1業務処理に対応する単一クラス。Eloquent/外部APIを直接操作 | 複数業務の混在（SRP遵守） |
| DTO | UseCaseへ渡す入力値の型安全なコンテナ | ロジックの実装 |
| Model | テーブル定義・リレーション・Scope/Accessor | 業務ロジック |
| Service | 外部API呼び出し・暗号化など横断的処理の集約 | ビジネスルールの判定 |
| Job | キューを通じた非同期処理の実行単位 | 同期的な処理への流用 |

## 3. モデル詳細

### Company

```php
final class Company extends Model
{
    use SoftDeletes;

    // リレーション
    public function users(): HasMany  // 所属ユーザー一覧
}
```

### User

```php
final class User extends Authenticatable
{
    use SoftDeletes;

    // リレーション
    public function company(): BelongsTo
    public function dailyLogs(): HasMany
    public function analyses(): HasMany          // 部下として作成した要約
    public function receivedAnalyses(): HasMany  // 上司として受け取った要約（viewer_id）

    // スコープ
    public function scopeInSameCompany(Builder $query, int $companyId): void

    // アクセサ
    public function getIsManagerAttribute(): bool  // is_admin=falseのユーザー全員が対象
}
```

### Question

```php
final class Question extends Model
{
    use SoftDeletes;

    // スコープ
    public function scopeActive(Builder $query): void
}
```

### DailyLog

```php
final class DailyLog extends Model
{
    use SoftDeletes;

    // answer_text はDB保存時/取得時に自動暗号化/復号しない
    // EncryptionService を UseCase 層で明示的に使う

    // リレーション
    public function user(): BelongsTo
    public function question(): BelongsTo

    // スコープ
    public function scopeForUser(Builder $query, int $userId): void
    public function scopeWithinDateRange(Builder $query, Carbon $from, Carbon $to): void
}
```

### Analysis

```php
final class Analysis extends Model
{
    use SoftDeletes;

    // リレーション
    public function user(): BelongsTo    // 部下
    public function viewer(): BelongsTo  // 上司

    // スコープ
    public function scopePublished(Builder $query): void
    public function scopeVisibleToViewer(Builder $query, int $viewerId): void
    public function scopeNotExpired(Builder $query): void  // published_at から7日以内

    // アクセサ
    public function getIsExpiredAttribute(): bool
}
```

### SystemSetting

```php
final class SystemSetting extends Model
{
    // ソフトデリートなし

    // 静的ヘルパー
    public static function getMasterDate(): Carbon
    public static function setMasterDate(Carbon $date): void
}
```

## 4. Job 設計

### ProcessVoiceLogJob

```
役割: S3から音声ファイルを取得 → Whisper APIで文字起こし → DB保存（AES-256暗号化） → S3から音声削除
キュー: default
タイムアウト: 120秒
リトライ: 3回
```

### GenerateAnalysisJob

```
役割: 対象ログを取得・復号 → GPT-4oへプロンプト送信 → 要約結果をanalysesテーブルへ保存 → AnalysisCompletedイベントを発火
キュー: ai-processing（専用キュー）
タイムアウト: 180秒
リトライ: 2回
```

## 5. イベント/リスナー

| イベント | リスナー | 処理 |
|---|---|---|
| `AnalysisCompleted` | `BroadcastAnalysisCompleted` | Reverb経由でVue.jsへWebSocket通知 |
