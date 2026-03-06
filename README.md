# 1on1ツール

マネージャーと部下の1on1コミュニケーションを支援するツールです。部下が日常のふとした瞬間に本音の感情を記録し、AIが要約してマネージャーに共有します。

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

## ドキュメント

詳細仕様は `docs/` を参照してください。

- `requirements.md` — 機能要件・ユーザーフロー
- `db-schema.md` — テーブル定義・制約
- `page-details.md` — ロール別UI/画面設計
- `daily-input-voice-phase-tech-spec.md` — 音声入力フロー技術仕様
- `output-generation-ai-summary-spec.md` — 非同期AIジョブ+Reverb通知仕様
