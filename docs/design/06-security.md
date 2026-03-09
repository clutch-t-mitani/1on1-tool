# セキュリティ設計

## 1. 暗号化設計

### 対象データ

`daily_logs.answer_text`（生の感情ログ）のみ暗号化する。

### 暗号化方式

Laravel 組み込みの `Crypt` ファサード（AES-256-CBC）を使用する。暗号化キーは `.env` の `APP_KEY` を利用する。

### EncryptionService

```php
final class EncryptionService
{
    public function encrypt(string $plainText): string
    {
        return Crypt::encryptString($plainText);
    }

    public function decrypt(string $cipherText): string
    {
        return Crypt::decryptString($cipherText);
    }
}
```

### 復号のタイミング

復号は以下の場面でのみ行い、復号された文字列をDBや外部ストレージに書き出さない。

| 処理 | 復号タイミング |
|---|---|
| AI要約生成 | `GenerateAnalysisJob` のメモリ内のみ。処理終了後にGCが回収 |
| 管理者の生ログ閲覧 | レスポンス生成直前。Resourceクラス内で復号しない（UseCaseで復号してResourceへ渡す） |

### 暗号化の保証範囲

| 保存場所 | 状態 |
|---|---|
| MySQL（`daily_logs.answer_text`） | 暗号化済み |
| AWS S3（音声ファイル） | 一時保存のみ、文字起こし後即物理削除 |
| Laravelログファイル | 生ログを絶対に出力しない（例外メッセージ等にも含めない） |
| Redis（キュー） | Jobのペイロードには `userId` と `s3Path` のみ。平文テキストは乗せない |

---

## 2. 監査ログ設計

管理者による特権操作を記録する。

### 記録対象操作

| 操作 | 記録内容 |
|---|---|
| 生ログ閲覧 | `admin_id`, `target_user_id`, `log_id`, 操作日時 |
| 生ログ削除 | `admin_id`, `target_user_id`, `log_id`, 操作日時 |
| システム日付変更 | `admin_id`, `before_date`, `after_date`, 操作日時 |
| ユーザー削除 | `admin_id`, `target_user_id`, 操作日時 |

### 実装方針

専用テーブル `audit_logs` に記録する。UseCase層でデータ操作の直後に `AuditLogger` サービスを呼び出す。

```
audit_logs テーブル
- id
- admin_id  (FK: users.id)
- action    (VARCHAR: 'view_daily_log', 'delete_daily_log', 'update_system_date', etc.)
- target_type  (VARCHAR: 'DailyLog', 'User', 'SystemSetting', etc.)
- target_id    (BIGINT, nullable)
- metadata     (JSON: before/after値など補足情報)
- created_at
```

---

## 3. その他のセキュリティ対策

### CSRF対策

Laravel Sanctum のセッション認証を使用するため、`/api` ルートには Sanctum の CSRF 保護が適用される。Vue.js は初回リクエスト前に `GET /sanctum/csrf-cookie` を呼び出してトークンを取得する。

### SQLインジェクション対策

Eloquent ORM のクエリビルダを使用し、生クエリ（`DB::statement` 等）は使わない。やむを得ない場合はバインディングパラメーターを必ず使用する。

### 音声ファイルの取り扱い

- S3のバケットはパブリックアクセスを無効化する
- 保存パスは `temp/audio/{uuid}.{ext}` とし、ユーザーIDを含めない（推測不可能なパスにする）
- 文字起こし完了後は必ず `Storage::disk('s3')->delete()` で物理削除する
- Jobが失敗した場合もフックで削除処理を実行する（`failed()` メソッドに実装）

### パスワード

Laravel デフォルトの `bcrypt`（コスト12）でハッシュ化する。

### レート制限

ログインエンドポイントには `throttle:5,1`（1分あたり5回）を適用する。

```php
Route::middleware('throttle:5,1')->post('/login', ...);
```
