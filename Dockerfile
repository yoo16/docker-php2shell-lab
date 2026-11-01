# Step 1: 更新前のバージョン（教材の初期状態）
FROM php:8.4.26-fpm

# Step 2: パッチ適用演習では、上の FROM をコメントアウトして次の行を有効にする
# FROM php:8.4.27-fpm

# 補足
# この Dockerfile は、PHP が組み込まれた公式イメージを土台として使用します。
# PHP 公式イメージ自体は PHP のソースコードからビルドされていますが、利用者がここで PHP 本体をコンパイルしているわけではありません。
# Kubernetes も通常はビルド済みのコンテナイメージを実行します。イメージのビルドは CI/CD などで別途行います。
# コンテナを使わない Linux サーバーでは、保守しやすい OS のパッケージマネージャーを使うのが一般的です。
# ソースコードからのコンパイルも可能ですが、依存関係・更新・脆弱性対応を自分で管理する必要があります。

# Step 3: PHP の拡張モジュールをインストール
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install zip intl mbstring pdo_mysql bcmath \
    && rm -rf /var/lib/apt/lists/*

# Step 4: Composer をインストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Step 5: 作業ディレクトリを設定
WORKDIR /var/www/html
