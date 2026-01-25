# GitHub Copilot Instructions

## プロジェクト概要

- 目的: Discord にメッセージを送信する軽量な Web サーバーブリッジ
- 主な機能:
  - POST リクエストで JSON ペイロードを受け取る
  - Discord Bot API 経由で Discord チャンネルにメッセージを送信
  - プレーンテキストと埋め込みメッセージ形式の両方をサポート
  - 環境変数または URL パスでチャンネル ID を指定可能
- 対象ユーザー: Discord Bot を利用したメッセージ配信を必要とする開発者

## 共通ルール

- 会話は日本語で行う。
- コミットメッセージは [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) に従う。
  - 形式: `<type>(<scope>): <description>`
  - `<description>` は日本語で記載
  - 例: `feat: ユーザー認証機能を追加`
- ブランチ命名は [Conventional Branch](https://conventional-branch.github.io) に従う。
  - 形式: `<type>/<description>`
  - `<type>` は短縮形（feat, fix）を使用
  - 例: `feat/add-user-auth`
- 日本語と英数字の間には半角スペースを入れる。

## 技術スタック

- 言語: PHP 8
- ランタイム: PHP CLI (built-in server)
- コンテナ化: Docker (Alpine Linux ベース)
- 設定管理: 環境変数 (.env ファイル)
- パッケージマネージャー: なし (外部依存なし)
- CI/CD: GitHub Actions

## コーディング規約

- コード内のコメント: 日本語で記載
- エラーメッセージ: 英語で記載
- 既存のコードスタイルに従う
- 関数やクラスには docstring を日本語で記載

## 開発コマンド

```bash
# Docker イメージをビルド
docker build src/ -t discord-deliver

# docker-compose で起動（本番）
docker-compose up

# サンプル・テストを実行
cd example && docker-compose up

# シェルスクリプトの Lint
shellcheck src/entrypoint.sh
```

## テスト方針

- テストフレームワーク: GitHub Actions CI + ShellCheck
- GitHub Actions で Docker イメージのビルド検証を実施
- ShellCheck で `.sh` ファイルの Lint を実施（SC2086, SC2001 を除外）
- `example/` ディレクトリで実際の動作確認が可能

## セキュリティ / 機密情報

- `DISCORD_TOKEN` などの認証情報は `.env` ファイルで管理し、Git にコミットしない。
- `.gitignore` に `*.env` と `data/*` を追加済み。
- ログに個人情報や認証情報を出力しない。

## ドキュメント更新

以下のファイルは変更時に更新が必要：

- `README.md`: 機能追加や仕様変更時
- `CHANGELOG.md`: バージョンアップ時（存在する場合）
- `.github/workflows/*.yml`: CI/CD の変更時
- `docker-compose.yml`: コンテナ構成の変更時

## リポジトリ固有

- このプロジェクトは Docker Hub に公開されている (`book000/discord-deliver`)。
- マルチプラットフォームビルド (linux/amd64, linux/arm64) に対応。
- Renovate により依存関係が自動更新される。**Renovate が作成した PR には追加コミットや更新を行わない。**
- API エンドポイント:
  - `POST /`: 環境変数 `DISCORD_CHANNEL_ID` を使用
  - `POST /{channel_id}`: URL で指定したチャンネル ID を使用
- リクエストボディ形式:
  - `content`: プレーンテキストメッセージ
  - `embed`: 埋め込みメッセージオブジェクト
- 必須環境変数:
  - `DISCORD_TOKEN`: Bot 認証トークン
  - `DISCORD_CHANNEL_ID`: デフォルトチャンネル ID（オプション）
