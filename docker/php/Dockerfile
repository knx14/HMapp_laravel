# PHP-FPM ベースのイメージ (Alpine版)
FROM php:8.2-fpm-alpine

# 必要なパッケージをインストール
# - zip, unzip: Composerで必要
# - curl: Composerのダウンロードに必要
# - libpng-dev, libjpeg-dev, libfreetype6-dev: GD拡張の依存関係
# - libonig-dev: mbstring拡張の依存関係
# - git: ローカル開発環境でComposerがGitリポジトリからパッケージをインストールする場合に必要
#   本番環境では不要だが、docker-compose.ymlでcomposer installを実行するため、ここでは含める
RUN apk add --no-cache \
    zip \
    unzip \
    curl \
    git \
    libpng-dev \
    libjpeg-dev \
    freetype-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql bcmath mbstring \
    && apk del --no-cache libpng-dev libjpeg-dev freetype-dev oniguruma-dev \
    && rm -rf /var/cache/apk/*

# Composer のインストール
COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# Laravelのキャッシュやログ書き込みのためのパーミッション設定
# www-data は多くのPHP-FPMコンテナで使われるユーザー
# ストレージとキャッシュディレクトリのパーミッションを設定
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP-FPM のデフォルトポートを公開
EXPOSE 9000

# コンテナ起動時のデフォルトコマンド (php-fpmを起動)
CMD ["php-fpm"]


#以前のローカル開発時のもの
# # PHP-FPM ベースのイメージ
# FROM php:8.2-fpm

# # 必要なパッケージをインストール
# RUN apt-get update && apt-get install -y \
#     zip unzip git curl libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
#     && docker-php-ext-configure gd --with-freetype --with-jpeg \
#     && docker-php-ext-install gd pdo pdo_mysql bcmath mbstring

# # Composer のインストール
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # 作業ディレクトリの設定
# WORKDIR /var/www/html