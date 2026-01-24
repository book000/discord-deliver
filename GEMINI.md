# GEMINI.md

## 目的
- Gemini CLI 向けのコンテキストと作業方針を定義する。

## 出力スタイル
- 言語: 日本語
- トーン: 簡潔で事実ベース
- 形式: Markdown

## 共通ルール
- 会話は日本語で行う。
- PR とコミットは Conventional Commits に従う。
- PR タイトルとコミット本文の言語: PR タイトルは Conventional Commits 形式（英語推奨）。PR 本文は日本語。コミットは Conventional Commits 形式（description は日本語）。
- 日本語と英数字の間には半角スペースを入れる。

## プロジェクト概要
Web server to send messages to Discord for use with Docker. Accepts JSON POST requests and forwards them to Discord channels via bot token.

### 技術スタック
- **言語**: PHP
- **フレームワーク**: None (raw HTTP API)
- **主要な依存関係**:
  - runtime:
    - PHP 7.2+

## コーディング規約
- フォーマット: 既存設定（ESLint / Prettier / formatter）に従う。
- 命名規則: 既存のコード規約に従う。
- コメント言語: 日本語
- エラーメッセージ: 英語

### 開発コマンド
```bash
# deploy
Docker Compose - docker-compose.yml

# runtime
PHP with Docker container

```

## 注意事項
- 認証情報やトークンはコミットしない。
- ログに機密情報を出力しない。
- 既存のプロジェクトルールがある場合はそれを優先する。

## リポジトリ固有
- **type**: Docker Service
- **entry_point**: src/main.php
**environment_variables:**
  - DISCORD_TOKEN (required)
  - DISCORD_CHANNEL_ID (optional)
- **api_endpoint**: POST /{channel_id} with JSON body {content, embed}
- **api_version**: Discord API v10