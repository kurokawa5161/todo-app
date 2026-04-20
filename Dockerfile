# ベースイメージ
FROM php:8.3-fpm

# 作業ディレクトリを設定
WORKDIR /var/www/html

# 必要なパッケージとPHP拡張機能をインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# アプリケーションファイルをコピー
COPY . /var/www/html

# 依存関係をインストール
RUN composer install --no-dev --optimize-autoloader

# ストレージとキャッシュのパーミッション設定
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ポート9000を公開
EXPOSE 9000

CMD ["php-fpm"]
