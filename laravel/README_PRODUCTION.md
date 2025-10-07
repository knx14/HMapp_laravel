# HMapp - 本番環境デプロイガイド

## 概要
このブランチは本番環境用に最適化されています。開発用のシーダーファイルやテストデータは削除されています。

## デプロイ手順

### 1. 環境設定
```bash
# .envファイルをコピーして設定
cp .env.production.example .env

# アプリケーションキーを生成
php artisan key:generate

# データベース設定を確認
# DB_CONNECTION=mysql
# DB_HOST=your_host
# DB_DATABASE=your_database
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 2. 依存関係のインストール
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. データベースのセットアップ
```bash
# マイグレーション実行
php artisan migrate --force

# 本番環境ではシーダーは実行しません
# 必要に応じて手動でユーザーを作成してください
```

### 4. 権限設定
```bash
# ストレージとキャッシュの権限設定
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. 最適化
```bash
# 設定キャッシュ
php artisan config:cache

# ルートキャッシュ
php artisan route:cache

# ビューキャッシュ
php artisan view:cache
```

## 削除されたファイル
- `database/seeders/AppUsersSeeder.php`
- `database/seeders/FarmSeeder.php`
- `database/seeders/FixBoundaryPolygonSeeder.php`
- `database/seeders/MeasurementSeeder.php`
- `database/database.sqlite`

## 注意事項
- 本番環境では`APP_DEBUG=false`に設定してください
- データベースはMySQLまたはPostgreSQLを使用してください
- SSL証明書の設定を忘れずに行ってください
- 定期的なバックアップを設定してください
