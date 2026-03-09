# 詳細設計 インデックス

## ドキュメント構成

| ファイル | 内容 |
|---|---|
| [01-architecture.md](./01-architecture.md) | アプリケーション構造・クラス設計 |
| [02-api.md](./02-api.md) | APIエンドポイント一覧・リクエスト/レスポンス仕様 |
| [03-auth.md](./03-auth.md) | 認証・認可設計 |
| [04-daily-log.md](./04-daily-log.md) | 日次ログ入力機能の詳細設計 |
| [05-analysis.md](./05-analysis.md) | AI要約生成・注釈・公開機能の詳細設計 |
| [06-security.md](./06-security.md) | セキュリティ設計（暗号化・監査ログ） |

## システム全体構成図

```
ブラウザ（Vue.js）
    │
    │ HTTP / WebSocket
    ▼
Laravel（API サーバー）
    ├── Controller  → UseCase → Model（Eloquent）→ MySQL
    ├── Job（Queue） → OpenAI API / AWS S3
    └── Reverb（WebSocket）← Job 完了イベント
```

## 前提ドキュメント（要件・DB）

- [requirements.md](../requirements.md) — 機能要件・プロダクト思想
- [db-schema.md](../db-schema.md) — テーブル定義・制約
- [page-details.md](../page-details.md) — 画面一覧・遷移図
- [daily-input-voice-phase-tech-spec.md](../daily-input-voice-phase-tech-spec.md) — 音声入力フロー
- [output-generation-ai-summary-spec.md](../output-generation-ai-summary-spec.md) — AI要約フロー
