# HMapp - 農業データ分析・管理システム

## 概要

HMappは、農業データの収集、分析、可視化を行うWebアプリケーションです。圃場管理、測定データのアップロード、分析結果の可視化などの機能を提供します。

## 主な機能

### 🚜 圃場管理
- 圃場の登録・管理
- 圃場の境界線データ（ポリゴン）の保存
- 栽培方法・作物種別による検索・フィルタリング
- 圃場所有者の管理

### 👥 ユーザー管理
- アプリユーザーの登録・管理
- 認証機能（Laravel Breeze）
- プロフィール管理

### 📈 推定結果表示
- 分析サマリー一覧
- 圃場別・日付別のデータ表示
- 詳細分析データのAPI提供

## 技術スタック

### バックエンド
- **PHP 8.2+**
- **Laravel 12.0** - Webフレームワーク
- **SQLite** - データベース（開発環境）
- **MySQL** - 本番環境（AWS RDS）

### フロントエンド
- **Tailwind CSS** - スタイリング
- **Alpine.js** - 軽量JavaScriptフレームワーク
- **Vite** - ビルドツール

### インフラ・開発環境
- **Docker** - コンテナ化
- **Nginx** - Webサーバー
- **PHP-FPM** - PHP実行環境
- **phpMyAdmin** - データベース管理

## システム要件

- PHP 8.2以上
- Composer
- Node.js & npm
- Docker & Docker Compose

## セットアップ

### 1. リポジトリのクローン
```bash
git clone <repository-url>
cd HMapp_laravel
```

### 2. Docker環境での起動
```bash
# Dockerコンテナのビルドと起動
docker-compose up -d

# Laravelアプリケーションのセットアップ
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
```

### 3. フロントエンドのセットアップ
```bash
# Node.js依存関係のインストール
npm install

# アセットのビルド
npm run build
```

### 4. 開発環境での実行
```bash
# 開発サーバーの起動
composer run dev
```

## プロジェクト構造

```
HMapp_laravel/
├── docker/                 # Docker設定ファイル
│   ├── nginx/             # Nginx設定
│   └── php/               # PHP-FPM設定
├── laravel/               # Laravelアプリケーション
│   ├── app/
│   │   ├── Http/Controllers/    # コントローラー
│   │   ├── Models/             # Eloquentモデル
│   │   └── View/Components/    # Bladeコンポーネント
│   ├── database/
│   │   ├── migrations/         # データベースマイグレーション
│   │   └── seeders/            # シーダー
│   ├── resources/views/        # Bladeテンプレート
│   └── routes/                 # ルート定義
└── docker-compose.yml      # Docker Compose設定
```

## 主要なモデル

### AppUser
アプリケーションのユーザー管理

### Farm
圃場情報の管理
- 圃場名、栽培方法、作物種別
- 境界線ポリゴンデータ

### Upload
測定データのアップロード管理
- ファイルパス、測定日時

### AnalysisResult
分析結果の保存
- 緯度・経度情報
- 分析パラメータと値

## API エンドポイント

### 分析データ
- `GET /api/analysis/summary` - 分析サマリー一覧
- `GET /api/uploads/{uploadId}/analysis-data` - 詳細分析データ

### 圃場データ
- `GET /api/farms/{farmId}/boundary` - 圃場境界線データ
- `GET /api/farms/{farmId}/measurements` - 圃場測定データ

## データベース設計

### 主要テーブル
- `app_users` - アプリユーザー
- `farms` - 圃場情報
- `uploads` - アップロードデータ
- `analysis_results` - 分析結果
- `result_values` - 分析値詳細


## 開発・デバッグ

### ログの確認
```bash
docker-compose exec php php artisan pail
```

### データベースのリセット
```bash
docker-compose exec php php artisan migrate:fresh --seed
```

### テストの実行
```bash
docker-compose exec php php artisan test
```

## 本番環境

### 環境変数
本番環境では以下の環境変数を設定：

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

### デプロイ
1. 本番サーバーでのDocker環境構築
2. 環境変数の設定
3. データベースマイグレーションの実行
4. アセットのビルド
5. 権限設定

