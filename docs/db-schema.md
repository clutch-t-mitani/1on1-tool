# DB構成

## 1. テーブル一覧

システムを構成する全6テーブルの概要です。

| **テーブル名** | **論理名** | **説明** | **ソフトデリート** |
| --- | --- | --- | --- |
| `companies` | 会社 | 利用企業を管理。所属ユーザーのバリデーションに使用。 | ✅ |
| `users` | ユーザー | システム管理者、上司、部下の全ユーザー。 | ✅ |
| `questions` | 質問マスタ | 日々の入力で提示される質問文。 | ✅ |
| `daily_logs` | 日々の記録 | ユーザーが投稿した生の回答データ。 | ✅ |
| `analyses` | AI要約・アウトプット | AIが生成した要約と本人の注釈。閲覧者（上司）を1人指定。 | ✅ |
| `system_settings` | システム設定 | マスター日付の上書きなど、システム全体の制御用。 | ❌ |

## 2. 各テーブル詳細仕様

### 2.1 companies (会社)

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | 会社ID |
| `name` | varchar(255) | NO |  | 会社名 |
| `created_at` | timestamp | YES |  | 作成日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |
| `deleted_at` | timestamp | YES |  | 削除日時（論理削除用） |

### **2.2 users (ユーザー)**

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | ユーザーID |
| `company_id` | bigint | NO | FK | 所属会社ID |
| `name` | varchar(255) | NO |  | 氏名 |
| `email` | varchar(255) | NO | Unique | メールアドレス |
| `password` | varchar(255) | NO |  | パスワード（ハッシュ化） |
| `is_admin` | boolean | NO |  | システム管理者フラグ（true: 管理者） |
| `created_at` | timestamp | YES |  | 作成日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |
| `deleted_at` | timestamp | YES |  | 削除日時（論理削除用） |

### **2.3 questions (質問マスタ)**

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | 質問ID |
| `company_id` | bigint | NO | FK | 所属会社ID |
| `content` | text | NO |  | 質問文 |
| `is_active` | boolean | NO |  | 有効フラグ（falseで非表示） |
| `created_at` | timestamp | YES |  | 作成日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |
| `deleted_at` | timestamp | YES |  | 削除日時（論理削除用） |

### **2.4 daily_logs (日々の記録)**

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | ログID |
| `user_id` | bigint | NO | FK | 入力したユーザーID |
| `question_id` | bigint | NO | FK | 回答対象の質問ID |
| `answer_text` | text | NO |  | 回答内容（生のテキスト） |
| `created_at` | timestamp | YES |  | 投稿日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |
| `deleted_at` | timestamp | YES |  | 削除日時（論理削除用） |

### **2.5 analyses (AI要約・アウトプット)**

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | 要約ID |
| `user_id` | bigint | NO | FK | 部下（作成者）のID |
| `viewer_id` | bigint | NO | FK | **閲覧者（部下が選択した同じ会社の上司）のID** |
| `summary_content` | text | NO |  | AIが生成した要約テキスト |
| `annotation_text` | text | YES |  | 部下による補足・注釈 |
| `published_at` | timestamp | YES |  | 公開日時 |
| `created_at` | timestamp | YES |  | 作成日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |
| `deleted_at` | timestamp | YES |  | 削除日時（論理削除用） |

### **2.6 system_settings (システム設定)**

| **カラム名** | **データ型** | **NULL** | **キー** | **説明** |
| --- | --- | --- | --- | --- |
| `id` | bigint | NO | PK | 設定ID |
| `key` | varchar(255) | NO | Unique | 設定項目名（`master_date_override`など） |
| `value` | varchar(255) | YES |  | 設定値 |
| `created_at` | timestamp | YES |  | 作成日時 |
| `updated_at` | timestamp | YES |  | 更新日時 |

## 3. 外部キー制約・インデックスに関する共通設計

- **外部キー（Foreign Key）:** * データの整合性を守るため、すべてのID参照（`user_id`, `company_id`など）に設定。
    - ただし、削除時はDB側の `CASCADE` ではなく、Laravel側のソフトデリートで制御する。
- **ソフトデリート（Soft Delete）:**
    - `system_settings` を除く全テーブルに適用。
    - 削除されたデータは `deleted_at` に日時が入り、通常の検索（Eloquent）からは自動的に除外される。
- **バリデーション（Validation）:**
    - `analyses.viewer_id` の登録時、`users.company_id` が作成者と同じであることをアプリケーション層で必ずチェックする。
