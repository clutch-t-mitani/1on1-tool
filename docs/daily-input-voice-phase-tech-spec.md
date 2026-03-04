# 日々入力(音声入力の場合)フェーズ：技術仕様書

## **1. システム構成・技術スタック**

- **文字起こしエンジン:** OpenAI Whisper API (v3)
- **ストレージ:** AWS S3 (一時保存用)
- **キュー管理:** Redis / Laravel Horizon

## **2. 処理フロー（シーケンス）**

| ステップ | アクション | 詳細内容 |
| --- | --- | --- |
| 1. 録音 | Vue.js | 録音中は波形アニメーションのみを表示。自己検閲（書き直し）を防ぐため、リアルタイムの文字起こし結果は見せない。 |
| 2. 送信 | Vue.js → API | 録音終了後、音声ファイルをLaravelのAPIエンドポイントへPOST送信。 |
| 3. 受付 | Laravel | ファイルを受け取り、AWS S3の一時ディレクトリ（temp/audio/）へ保存。 |
| 4. ジョブ投入 | Laravel | 文字起こしを実行する非同期ジョブ（ProcessVoiceLogJob）をQueueへ投入し、フロントへ即座にレスポンスを返す。 |
| 5. 文字起こし | Laravel Job | OpenAI Whisper APIを呼び出し。音声ファイルを高精度な日本語テキストへ変換。 |
| 6. 暗号化保存 | Laravel Job | 変換されたテキストをDBの daily_logs テーブルへ保存。この際、アプリケーション層でAES-256暗号化を施す。 |
| 7. 破棄 | Laravel Job | 保存完了後、S3上の一時音声ファイルを即座に物理削除。サーバー側に音声を残さない。 |

### 音声処理シーケンス図

```mermaid
sequenceDiagram
    autonumber
    participant F as Vue.js (部下)
    participant B as Laravel (API)
    participant S as AWS S3
    participant Q as Redis (Queue)
    participant W as OpenAI Whisper

    F->>B: 音声バイナリをPOST送信
    B->>S: 音声ファイルを一時保存
    B->>Q: 変換Job (ProcessVoiceLog) を登録
    B-->>F: 受付完了を即座にレスポンス
    Note over F: 録音完了画面へ遷移

    Q->>W: S3の音声を文字起こしリクエスト
    W-->>Q: テキストデータを返却
    Q->>B: DBへ暗号化保存 (AES-256)
    Q->>S: 一時音声ファイルを物理削除
    Note over B,S: サーバーに音声は残らない
