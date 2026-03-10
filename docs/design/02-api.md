# APIエンドポイント仕様

## 共通仕様

- **ベースURL:** `/api`
- **認証方式:** Laravel Sanctum（セッションベース）
- **レスポンス形式:** JSON
- **日時フォーマット:** ISO 8601（`2026-03-04T12:00:00+09:00`）

### 共通エラーレスポンス

```json
{
    "message": "エラーの説明",
    "errors": {
        "フィールド名": ["バリデーションエラーメッセージ"]
    }
}
```

| HTTPステータス | 意味 |
|---|---|
| 400 | バリデーションエラー |
| 401 | 未認証 |
| 403 | 権限なし |
| 404 | リソースが存在しない |
| 422 | 業務ルール違反（例：5日分未満で要約実行） |
| 500 | サーバーエラー |

---

## 1. 認証

### POST /api/login

ログイン。

**リクエスト**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**レスポンス 200**
```json
{
    "user": {
        "id": 1,
        "name": "山田 太郎",
        "email": "user@example.com",
        "is_admin": false,
        "company_id": 1
    }
}
```

### GET /api/user

認証済みユーザーの情報を返す。SPA起動時の認証状態確認に使用。

**ミドルウェア:** `auth`

**レスポンス 200**
```json
{
    "user": {
        "id": 1,
        "name": "山田 太郎",
        "email": "user@example.com",
        "is_admin": false,
        "company_id": 1
    }
}
```

### POST /api/logout

ログアウト。

**レスポンス 204** （本文なし）

---

## 2. 日次ログ（部下）

### POST /api/daily-logs/text

テキストで1問分の回答を保存する。

**ミドルウェア:** `auth`

**リクエスト**
```json
{
    "question_id": 2,
    "answer_text": "今日は設計レビューがうまくいった。"
}
```

**レスポンス 201**
```json
{
    "message": "保存しました。"
}
```

**業務ルール**
- 同一ユーザー・同一質問・同日（日付変更線：翌日正午）の重複登録は不可（422）
- 保存後は本人も内容を取得できない

### POST /api/daily-logs/voice

音声ファイルを受け取り、文字起こしJobを投入する。

**ミドルウェア:** `auth`

**リクエスト（multipart/form-data）**
```
question_id: 2
audio: [音声ファイル（webm / mp4 / wav）, 最大25MB]
```

**レスポンス 202**
```json
{
    "message": "受け付けました。変換完了後に保存されます。"
}
```

### GET /api/daily-logs/status

今日の入力状況（4問分の完了状況）を返す。

**ミドルウェア:** `auth`

**レスポンス 200**
```json
{
    "total_days_logged": 7,
    "today_completed": true,
    "answered_question_ids": [1, 2, 3, 4]
}
```

---

## 3. AI要約（部下）

### POST /api/analyses

AI要約生成を開始する（Jobをキューに投入）。

**ミドルウェア:** `auth`

**レスポンス 202**
```json
{
    "message": "解析を開始しました。完了後に通知します。",
    "analysis_id": 5
}
```

**業務ルール**
- ログが5日分未満の場合は422
- 既に処理中のJobがある場合は422

### GET /api/analyses/{id}

要約結果と注釈を取得する。

**ミドルウェア:** `auth`

**レスポンス 200**
```json
{
    "id": 5,
    "summary_content": "今期を通じて、成果への達成感を多く感じている一方...",
    "annotation_text": null,
    "published_at": null,
    "created_at": "2026-03-04T10:00:00+09:00"
}
```

### PATCH /api/analyses/{id}/annotation

注釈を保存する（公開前のみ可）。

**ミドルウェア:** `auth`

**リクエスト**
```json
{
    "annotation_text": "特に3月上旬の山場が堪えていました。補足です。"
}
```

**バリデーション**
- `annotation_text`: 200文字以内、null可

**レスポンス 200**
```json
{
    "message": "注釈を保存しました。"
}
```

### POST /api/analyses/{id}/publish

要約を上司に公開する。

**ミドルウェア:** `auth`

**リクエスト**
```json
{
    "viewer_id": 3
}
```

**バリデーション**
- `viewer_id`: 同じ会社の別ユーザーであること

**レスポンス 200**
```json
{
    "message": "公開しました。",
    "published_at": "2026-03-04T15:00:00+09:00"
}
```

---

## 4. 上司閲覧

### GET /api/viewer/analyses

自分宛の公開済み要約一覧（7日以内）を返す。

**ミドルウェア:** `auth`

**レスポンス 200**
```json
{
    "data": [
        {
            "id": 5,
            "user_name": "田中 花子",
            "summary_content": "今期を通じて...",
            "annotation_text": "特に3月上旬の...",
            "published_at": "2026-03-04T15:00:00+09:00",
            "expires_at": "2026-03-11T15:00:00+09:00"
        }
    ]
}
```

### GET /api/viewer/analyses/{id}

要約の詳細を取得する。

**ミドルウェア:** `auth`

**業務ルール**
- 公開から7日を超えた場合は403

**レスポンス 200**（`GET /api/analyses/{id}` と同形式、`user_name` が追加）

---

## 5. WebSocket チャンネル

### プライベートチャンネル: `analysis.{userId}`

AI要約完了時にサーバーから通知される。

**イベント名:** `AnalysisCompleted`

**ペイロード**
```json
{
    "analysis_id": 5
}
```

---

## 6. 管理者API

全て `auth` + `admin` ミドルウェアが必要。

| メソッド | パス | 機能 |
|---|---|---|
| GET | `/api/admin/users` | ユーザー一覧 |
| POST | `/api/admin/users` | ユーザー作成 |
| PATCH | `/api/admin/users/{id}` | ユーザー更新 |
| DELETE | `/api/admin/users/{id}` | ユーザー削除（論理） |
| GET | `/api/admin/companies` | 会社一覧 |
| POST | `/api/admin/companies` | 会社作成 |
| GET | `/api/admin/daily-logs` | 生ログ一覧（復号あり） |
| DELETE | `/api/admin/daily-logs/{id}` | 生ログ削除 |
| GET | `/api/admin/analyses` | 要約一覧 |
| DELETE | `/api/admin/analyses/{id}` | 要約削除 |
| GET | `/api/admin/system-settings/date` | 現在のシステム日付取得 |
| PUT | `/api/admin/system-settings/date` | システム日付の上書き |
