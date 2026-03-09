# 認証・認可設計

## 1. 認証方式

Laravel Sanctum のセッション認証（Cookie）を使用する。SPAとAPIが同一オリジンのため、トークン認証ではなくセッションベースとする。

## 2. ロールと権限

システムにはロールを表すカラムは存在せず、`users.is_admin` フラグで判定する。

| ロール | 判定条件 | 説明 |
|---|---|---|
| システム管理者 | `is_admin = true` | 全データアクセス・特権操作が可能 |
| 一般ユーザー | `is_admin = false` | 部下・上司の両役割を兼ねる |

> 部下・上司の区別はロールではなく「どのデータに対して操作するか」で決まる。
> 同一ユーザーが別の1on1では上司にも部下にもなり得る。

## 3. ミドルウェア

| ミドルウェア名 | 処理内容 |
|---|---|
| `auth` | 未ログイン時に401を返す |
| `admin` | `is_admin = false` の場合に403を返す（`auth` と併用） |

```php
// routes/api.php の構成イメージ
Route::post('/login', [LoginController::class, 'store']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy']);
    Route::post('/daily-logs/text', [DailyLogController::class, 'storeText']);
    Route::post('/daily-logs/voice', [DailyLogController::class, 'storeVoice']);
    Route::get('/daily-logs/status', [DailyLogController::class, 'status']);
    Route::post('/analyses', [AnalysisController::class, 'store']);
    Route::get('/analyses/{id}', [AnalysisController::class, 'show']);
    Route::patch('/analyses/{id}/annotation', [AnalysisController::class, 'saveAnnotation']);
    Route::post('/analyses/{id}/publish', [AnalysisController::class, 'publish']);
    Route::get('/viewer/analyses', [ViewerAnalysisController::class, 'index']);
    Route::get('/viewer/analyses/{id}', [ViewerAnalysisController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        // 管理者ルート
    });
});
```

## 4. リソースアクセス制御

ミドルウェアによるロール判定に加え、UseCase層でもリソースの所有者チェックを行う。

### DailyLog

- `user_id` が認証ユーザーの `id` と一致すること

### Analysis（部下として操作）

- `user_id` が認証ユーザーの `id` と一致すること
- `published_at` が null（未公開）であること（注釈保存・公開操作時）

### Analysis（上司として閲覧）

- `viewer_id` が認証ユーザーの `id` と一致すること
- `published_at` が null でないこと
- `published_at` から7日以内であること

### viewer_id のバリデーション

公開先の上司を指定する際、以下をアプリケーション層で検証する。

```
analyses.viewer_id のユーザーの company_id
  ===
analyses.user_id のユーザーの company_id
```

## 5. セッション設定

| 設定項目 | 値 |
|---|---|
| セッションドライバー | `database`（`sessions` テーブル） |
| セッション有効期間 | 120分（操作ごとに延長） |
| Cookie の `HttpOnly` | `true` |
| Cookie の `Secure` | 本番環境: `true` |
| Cookie の `SameSite` | `lax` |
