# Gemini CLI 作業方針

## 目的

このファイルは、Gemini CLI がこのプロジェクトで作業を行う際のコンテキストと作業方針を定義します。

## 出力スタイル

- **言語**: 日本語で会話する
- **トーン**: 専門的かつ明確に
- **形式**: Markdown 形式で出力

## 共通ルール

- **会話言語**: 日本語
- **コミットメッセージ**: [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) に従う
  - 形式: `<type>(<scope>): <description>`
  - `<description>` は日本語で記載
  - 例: `fix: Discord API のエラーハンドリングを修正`
- **ブランチ命名**: [Conventional Branch](https://conventional-branch.github.io) に従う
  - 形式: `<type>/<description>`
  - `<type>` は短縮形（feat, fix）を使用
  - 例: `fix/api-error-handling`
- **日本語と英数字の間**: 半角スペースを挿入

## プロジェクト概要

- **プロジェクト名**: discord-deliver
- **目的**: Discord にメッセージを送信する軽量な Web サーバーブリッジ
- **主な機能**:
  - HTTP POST リクエストで JSON ペイロードを受け取る
  - Discord Bot API 経由で Discord チャンネルにメッセージを送信
  - プレーンテキストメッセージと埋め込みメッセージの両方をサポート
  - チャンネル ID を環境変数または URL パスで指定可能
  - Docker コンテナで軽量に動作

## 技術スタック

- **言語**: PHP 8
- **ランタイム**: PHP CLI (built-in server)
- **コンテナ化**: Docker (Alpine Linux ベース)
- **設定管理**: 環境変数（`discord-deliver.env` / `example.env` などの `*.env` を `env_file` で読み込み）
- **パッケージマネージャー**: なし（外部依存なし）
- **CI/CD**: GitHub Actions
- **依存関係更新**: Renovate

## コーディング規約

- **コード内コメント**: 日本語で記載
- **エラーメッセージ**: 英語で記載
- **フォーマット**: 既存のコードスタイルに従う
- **命名規則**: PHP の標準的な命名規則に従う
- **docstring**: 関数やクラスには日本語で docstring を記載

## 開発コマンド

```bash
# Docker イメージをビルド
docker build src/ -t discord-deliver

# docker-compose で起動（本番環境）
docker-compose up

# サンプル・テストを実行
cd example && docker-compose up

# シェルスクリプトの Lint
shellcheck src/entrypoint.sh

# GitHub Actions と同等の Docker ビルド検証
cd src && docker build . --file Dockerfile
```

## 注意事項

### セキュリティ / 機密情報

- **認証情報のコミット禁止**: `DISCORD_TOKEN` などの認証情報は `.env` ファイルで管理し、Git にコミットしない
- **ログへの機密情報出力禁止**: 認証トークンやチャンネル ID をログに出力しない
- **`.gitignore` の遵守**: `*.env` と `data/*` は既に除外設定済み

### 既存ルールの優先

- 既存のコードスタイルとパターンを優先する
- 外部依存を追加しない（このプロジェクトは依存ゼロを維持）
- 軽量性を維持する

### 既知の制約

- **PHP 8 のみ**: PHP のバージョンは 8 系を使用
- **Alpine Linux**: Docker イメージは Alpine Linux ベース
- **ポート 80**: コンテナは 80 番ポートを使用
- **Discord Bot API**: Discord の Bot API を使用してメッセージを送信
- **マルチプラットフォーム**: linux/amd64 と linux/arm64 に対応

## リポジトリ固有

- このプロジェクトは Docker Hub に公開されている (`book000/discord-deliver`)
- Renovate により Docker イメージや GitHub Actions の依存関係が自動更新される
- **Renovate が作成した PR には追加コミットや更新を行わない**
- **必須環境変数**:
  - `DISCORD_TOKEN`: Discord Bot の認証トークン（必須）
  - `DISCORD_CHANNEL_ID`: デフォルトのチャンネル ID（オプション）
- **API エンドポイント**:
  - `POST /`: 環境変数 `DISCORD_CHANNEL_ID` を使用してメッセージを送信
  - `POST /{channel_id}`: URL で指定したチャンネル ID を使用してメッセージを送信
- **サポートするリクエストボディ形式**:
  - `content`: プレーンテキストメッセージ
  - `embed`: 埋め込みメッセージオブジェクト（Discord の Embed 形式）
- **エラーレスポンス**:
  - `400 Bad Request`: リクエストボディが空など、リクエスト内容が不正な場合
  - `404 Not Found`: チャンネル ID が不正または未指定の場合
  - `405 Method Not Allowed`: POST 以外のメソッドでリクエストした場合
  - `415 Unsupported Media Type`: `Content-Type` が `application/json` でない場合
  - `500 Internal Server Error`: `DISCORD_TOKEN` が未設定など、サーバー内部エラーが発生した場合
  - 上記以外のエラーについては、Discord API が返した HTTP ステータスコードをそのまま返しつつ、レスポンスボディは `{status, message, response}` の形式でラップして返します
- **テスト方法**:
  - GitHub Actions で Docker イメージのビルドを検証
  - ShellCheck で `.sh` ファイルの Lint を実施
  - `example/` ディレクトリで実際の動作確認が可能
