# 開発ガイドライン

このファイルは、このリポジトリでコードを操作する際のClaude Code (claude.ai/code)向けのガイダンスを提供します。

## 絶対にやってはいけないこと

- `npm run build` を実行しない（vite dev serverに任せる。ビルド成果物が残るとトラブルの原因になる）
- テストやartisanコマンドをホストで直接実行しない（`./vendor/bin/sail` 経由で実行する）
- デザインシステムのコンポーネントを実装する際、Design System MCPで確認せずに実装しない

## 基本ルール

### 日本語表現
- 人が読むことを主目的とした文章は、読みやすい文章と表現を心がけ、簡潔にまとめる
- 意味が伝わりづらい略語や英語などを、文章内に含めない
- 共通の概念に関しては、共通の語句を使用して表現するように統一する
- 比喩などを用いて説明をしない

### 回答
- 日本語で簡潔かつ丁寧に回答してください
- **【重要】リファクタリングは指示がない限り絶対に行わないこと**
  - 動作しているコードを「改善」「整理」「簡潔化」などの目的で変更してはならない
  - 指示された機能追加・バグ修正以外のコード変更は禁止
  - 既存の実装パターンが理解できない場合でも、勝手に書き換えてはならない

## プロジェクト概要

マネージャーと部下の1on1コミュニケーションを支援するためのツール。部下が日常のふとした瞬間に本音の感情を記録し、AIが要約してマネージャーに共有する。

### ユーザーロール
- **部下（入力者）**: 日次感情ログ入力・AI要約確認・マネージャーへ公開
- **マネージャー（閲覧者）**: AI要約と注釈の閲覧（公開から7日間）
- **システム管理者**: 全データアクセス・システム日付変更・監査ログ

### コアワークフロー
1. **日次入力** → 音声/テキストで4問に回答 → Whisper APIで文字起こし → AES-256暗号化して `daily_logs` に保存（音声は即削除）
2. **AI分析** → 5件以上のログ蓄積後、部下がGPT-4oによる要約を起動 → 非同期Jobで処理 → Reverb WebSocketでリアルタイム通知
3. **公開** → 部下がマネージャーを選択し注釈を添えて公開 → `analyses` レコード生成 → 7日間閲覧可能

### データベース（6テーブル、`system_settings` 以外は論理削除あり）
- `companies` → `users`（`company_id` 外部キー、`is_admin` でロール判定）
- `questions` → `daily_logs`（回答はアプリ層でAES-256暗号化）
- `analyses`（`user_id`=部下、`viewer_id`=マネージャー、同一会社であること必須）
- `system_settings`（キー/値形式、管理者による日付上書きに使用）

### 外部サービス
- **OpenAI Whisper API v3**: 音声のテキスト変換
- **OpenAI GPT-4o**: 感情トレンドの要約生成
- **AWS S3**: 音声ファイルの一時保管（文字起こし後即削除）
- **Laravel Reverb**: AI処理完了のリアルタイム通知（WebSocket）

### インフラ（`compose.yaml` によるDocker構成）
- PHP 8.5（Laravel Sail）、MySQL 8.4、Redis、Meilisearch、Mailpit、Selenium

## 基本コマンド

### よく使うLaravel Sailコマンド
```bash
# Sailコマンドのエイリアス
./vendor/bin/sail artisan [command]
./vendor/bin/sail npm [command]
./vendor/bin/sail composer [command]
./vendor/bin/sail test
./vendor/bin/sail pint  # コード整形
```

### テスト
```bash
./vendor/bin/sail test                                        # 全テスト実行
./vendor/bin/sail test tests/Unit/ExampleTest.php             # 単一ファイル
./vendor/bin/sail artisan test --filter=ExampleTest           # テスト名でフィルタ
```

## Claude Code向けの重要な注意事項

### コーディング規則

**一般**
- PSR-12 準拠
- 省略のない意味明確な命名
- 複雑なクラス・メソッドには PHPDoc を記述
- マジックナンバー・ハードコード文字列は禁止

**PHP 8.5**
- `readonly` プロパティを可能な限り使用
- 定数の代替として `Enum` を使用
- コンストラクタプロパティプロモーションを活用
- 戻り値型は必ず厳密に指定（`true` / `false` / `never` 含む）
- Nullsafe Operator（`?->`）を使用
- 継承を想定しないクラスは `final` とする
- Named Arguments を用いて可読性を高める

**アーキテクチャ（ディレクトリ構成）**
```
app/
  UseCases/          # 業務処理（1クラス=1業務処理）
    User/
      CreateUserUseCase.php
  DTOs/              # UseCaseへの入力データ
    User/
      CreateUserInput.php
  Http/
    Controllers/     # HTTPリクエスト/レスポンス変換のみ
    Requests/        # バリデーション
    Resources/       # レスポンス整形
  Models/            # データ構造・リレーション定義のみ
```

**レイヤ責務**

| レイヤ | 責務 | 禁止事項 |
|---|---|---|
| Controller | リクエスト/レスポンス変換、バリデーション済みRequestをUseCaseへ渡す | ビジネスロジック、条件分岐、Model直接操作 |
| UseCase | 業務処理の唯一の集約点、Eloquent/DB/外部APIを直接扱う | 肥大化（SRP遵守） |
| Model | データ構造・リレーション定義、Scope/Accessorの使用は可 | 複雑な業務ロジック |

> Repositoryパターンは必須ではない（必要な場合のみ使用可）

### テストコード規約

- テストは `./vendor/bin/sail test` で実行（ホスト直実行禁止）
- 単体テスト: `tests/Unit/`、機能テスト: `tests/Feature/`
- テスト用DBはSQLite（`phpunit.xml` の `testing` 環境設定）

## 仕様書

詳細仕様は `docs/` を参照（ファイルリストは `README.md` を参照）。
