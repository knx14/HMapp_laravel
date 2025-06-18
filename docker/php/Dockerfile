# PHP-FPM ベースのイメージ (Alpine版を推奨)
FROM php:8.2-fpm-alpine

# 必要なパッケージをインストール (GDライブラリのJPEG/FreeTypeサポートを含む)
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    # GD拡張の依存関係をインストール
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # PHP拡張をインストール
    && docker-php-ext-install gd pdo pdo_mysql bcmath mbstring \
    # 不要になったaptキャッシュをクリーンアップしてイメージサイズを削減
    && rm -rf /var/lib/apt/lists/*

# Composer のインストール
COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# アプリケーションのコードをコンテナにコピー
# Dockerfileの場所 (docker/php/) から見て、プロジェクトルートの 'laravel' ディレクトリをコピー
COPY ../../laravel .

# Composer の依存関係をインストール (本番環境向けに開発用依存関係は除外)
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Laravelのキャッシュやログ書き込みのためのパーミッション設定
# www-data は多くのPHP-FPMコンテナで使われるユーザー
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP-FPM のデフォルトポートを公開 (docker-compose.yml でマッピングされる)
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