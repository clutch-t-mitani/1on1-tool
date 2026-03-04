# 1on1ツール

マネージャーと部下の1on1コミュニケーションを支援するツールです。部下が日常のふとした瞬間に本音の感情を記録し、AIが要約してマネージャーに共有します。

## 概要

### ユーザーロール

| ロール | 権限 |
|---|---|
| 部下（入力者） | 日次感情ログ入力・AI要約確認・マネージャーへ公開 |
| マネージャー（閲覧者） | AI要約と注釈の閲覧（公開から7日間） |
| システム管理者 | 全データアクセス・システム日付変更・監査ログ |

### コアワークフロー

1. **日次入力** — 音声またはテキストで4問に回答 → Whisper APIで文字起こし → AES-256暗号化して保存（音声は即削除）
2. **AI分析** — 5件以上のログ蓄積後、部下がGPT-4oによる要約を起動 → 非同期Jobで処理 → Reverb WebSocketでリアルタイム通知
3. **公開** — 部下がマネージャーを選択し注釈を添えて公開 → 7日間閲覧可能

## 技術スタック

- **バックエンド**: PHP 8.5 / Laravel（Laravel Sail）
- **データベース**: MySQL 8.4
- **キャッシュ・キュー**: Redis
- **リアルタイム通知**: Laravel Reverb（WebSocket）
- **音声認識**: OpenAI Whisper API v3
- **AI要約**: OpenAI GPT-4o
- **ファイルストレージ**: AWS S3（音声一時保管）
- **インフラ**: Docker（compose.yaml）

## セットアップ

### 必要環境

- Docker Desktop

### 起動手順

```bash
# 依存パッケージのインストール
composer install

# 環境設定ファイルの作成
cp .env.example .env

# Sailの起動
./vendor/bin/sail up -d

# アプリケーションキーの生成
./vendor/bin/sail artisan key:generate

# マイグレーションの実行
./vendor/bin/sail artisan migrate

# シーダーの実行（初期データ）
./vendor/bin/sail artisan db:seed
```

## 主要コマンド

```bash
# テスト実行
./vendor/bin/sail test

# コード整形
./vendor/bin/sail pint

# Artisanコマンド
./vendor/bin/sail artisan [command]

# npmコマンド
./vendor/bin/sail npm [command]
```

## データベース構成

6テーブル（`system_settings` 以外は論理削除あり）

- `companies` — 企業情報
- `users` — ユーザー（`company_id` 外部キー、`is_admin` でロール判定）
- `questions` — 質問マスタ
- `daily_logs` — 日次感情ログ（回答はAES-256暗号化）
- `analyses` — AI要約（`user_id`=部下、`viewer_id`=マネージャー）
- `system_settings` — システム設定（キー/値形式）

## ドキュメント

詳細仕様は `docs/` を参照してください。

- `requirements.md` — 機能要件・ユーザーフロー
- `db-schema.md` — テーブル定義・制約
- `page-details.md` — ロール別UI/画面設計
- `daily-input-voice-phase-tech-spec.md` — 音声入力フロー技術仕様
- `output-generation-ai-summary-spec.md` — 非同期AIジョブ+Reverb通知仕様
