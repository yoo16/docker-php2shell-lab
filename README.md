# docker-php2shell-lab

Linux + Nginx + PHP-FPM 構成で、設定ミスと脆弱性検証の切り分けを学ぶセキュリティ教材です。
検証環境は Docker Compose で再現します。

> ⚠️ 教材用の環境です。`127.0.0.1`（ローカル）でのみ実行し、実在のパスワードや API キーは置かないでください。

## 必要なもの

- Docker
- Docker Compose（`docker compose` コマンド）

## 起動

```bash
# ビルドして起動（初回やDockerfile変更時）
docker compose up -d --build

# 2回目以降（ビルド不要なら）
docker compose up -d
```

起動すると、次の順番で処理されます。

1. `composer-install` が `composer install` を実行して `src/vendor/` を生成（完了後に終了）
2. `php`（PHP-FPM）が起動
3. `nginx` が起動

ブラウザで次にアクセスします。

- http://localhost:8099/

## 停止・後片付け

```bash
# 停止（コンテナを削除）
docker compose down

# ログの確認
docker compose logs -f
docker compose logs nginx
docker compose logs php

# 状態の確認（composer-install は Exited(0) が正常）
docker compose ps -a
```

## Composer を手動で実行する

`php` サービスには制限用の `php.ini` が適用されており Composer が動かないため、
`tools` プロファイルの専用サービスを使います。

```bash
docker compose --profile tools run --rm composer install
docker compose --profile tools run --rm composer require <package>
docker compose --profile tools run --rm composer update
```

## ページ構成（public/）

| URL | 内容 |
| --- | --- |
| `/` | 環境チェック（ホーム） |
| `/preview.html` | 解説ページ（確認クイズを含む） |
| `/lab.html` | 実習ページ |
| `/glossary.html` | 用語集ページ |

## 実行ユーザーの確認（RCE の権限）

`exec-demo.php` などで PHP が実行される際のユーザーを確認します。
RCE が成立しても root ではなく `www-data`（UID 33）で動くことを確認できます。

```bash
# www-data の uid/gid を確認（docker compose 経由）
docker compose exec php id www-data

# docker exec で直接指定する場合（コンテナ名は docker compose ps で確認）
docker exec docker-php2shell-lab-php-1 id www-data
# → uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

> 補足: `exec-demo.php` の `ls -l` に出る `root root` は、一覧されたファイルの所有者であって、
> コマンドを実行したユーザーではありません。実行ユーザーは PHP-FPM ワーカーの `www-data` です。

## 確認用スクリプト（scripts/）

```bash
python3 scripts/check_php_version.py   # PHP バージョンの露出確認
python3 scripts/check_php_ini.py       # php.ini 設定の確認
python3 scripts/get_env.py             # .env が公開されていないかの確認
```
