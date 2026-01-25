# AI エージェント向け作業方針

## 目的

このファイルは、AI エージェント全般がこのプロジェクトで作業を行う際の共通方針を定義します。

## 基本方針

- **会話言語**: 日本語
- **コード内コメント**: 日本語
- **エラーメッセージ**: 英語
- **日本語と英数字の間**: 半角スペースを挿入
- **コミットメッセージ**: [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) に従う
  - 形式: `<type>(<scope>): <description>`
  - `<description>` は日本語で記載
  - 例: `feat: エラーハンドリングを改善`
- **ブランチ命名**: [Conventional Branch](https://conventional-branch.github.io) に従う
  - 形式: `<type>/<description>`
  - `<type>` は短縮形（feat, fix）を使用
  - 例: `feat/improve-error-handling`

## 判断記録のルール

技術的な判断を行う際は、以下を明示すること：

1. **判断内容**: 何を決定したか
2. **代替案**: 他にどのような選択肢があったか
3. **採用理由**: なぜこの案を選んだか
4. **前提条件**: どのような前提に基づいているか
5. **不確実性**: 何が不確実で、どのようなリスクがあるか

**重要**: 仮定を事実のように扱わず、前提条件と不確実性を明示すること。

## プロジェクト概要

- **プロジェクト名**: discord-deliver
- **目的**: Discord にメッセージを送信する軽量な Web サーバーブリッジ
- **技術スタック**:
  - 言語: PHP 8
  - ランタイム: PHP CLI (built-in server)
  - コンテナ化: Docker (Alpine Linux ベース)
  - CI/CD: GitHub Actions
- **主な機能**:
  - HTTP POST リクエストで JSON ペイロードを受け取る
  - Discord Bot API を使用してメッセージを送信
  - プレーンテキストと埋め込みメッセージの両方をサポート
  - チャンネル ID を環境変数または URL パスで指定可能

## 開発手順（概要）

1. **プロジェクト理解**
   - `README.md` と `src/main.php` を読む
   - Docker 構成を確認

2. **依存関係インストール**
   - このプロジェクトには外部依存パッケージはありません
   - Docker イメージをビルドするのみ

3. **変更実装**
   - 既存のコードスタイルに従う
   - コメントは日本語で記載
   - エラーメッセージは英語で記載

4. **テストと検証**
   - Docker イメージのビルドが成功することを確認
   - `.sh` ファイルを変更した場合は ShellCheck を実行
   - `example/` で動作確認

## コーディング規約

- **PHP コード**:
  - 既存のコードスタイルに従う
  - 関数には docstring を日本語で記載
  - エラーハンドリングを適切に行う
- **シェルスクリプト**:
  - ShellCheck で Lint を実行（SC2086, SC2001 を除外）
- **Docker**:
  - Alpine Linux ベースイメージを使用
  - マルチプラットフォームビルド (linux/amd64, linux/arm64) に対応

## 開発コマンド

```bash
# Docker イメージをビルド
docker build src/ -t discord-deliver

# docker-compose で起動
docker-compose up

# サンプル・テストを実行
cd example && docker-compose up

# シェルスクリプトの Lint
shellcheck src/entrypoint.sh
```

## セキュリティ / 機密情報

- **認証情報のコミット禁止**: `DISCORD_TOKEN` などの認証情報は `discord-deliver.env` / `example.env` などの `*.env` ファイルで管理し、Git にコミットしない
- **ログへの機密情報出力禁止**: 認証トークンやチャンネル ID をログに出力しない
- **`.gitignore` の遵守**: `*.env` と `data/*` は Git 管理対象外

## リポジトリ固有

- このプロジェクトは Docker Hub に公開されている (`book000/discord-deliver`)
- Renovate により Docker イメージや GitHub Actions の依存関係が自動更新される
- **Renovate が作成した PR には追加コミットや更新を行わない**
- **必須環境変数**:
  - `DISCORD_TOKEN`: Discord Bot の認証トークン
  - `DISCORD_CHANNEL_ID`: デフォルトのチャンネル ID（オプション）
- **API エンドポイント**:
  - `POST /`: 環境変数 `DISCORD_CHANNEL_ID` を使用
  - `POST /{channel_id}`: URL で指定したチャンネル ID を使用
- **サポートするリクエストボディ**:
  - `content`: プレーンテキストメッセージ
  - `embed`: 埋め込みメッセージオブジェクト
