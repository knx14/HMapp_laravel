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

### 2. ローカル開発環境のセットアップ

#### 2.1 環境変数ファイルの準備
```bash
cd laravel
cp .env.example .env
cd ..
```

#### 2.2 Docker環境での起動
# Dockerコンテナのビルドと起動（ローカル開発用）
docker-compose up -d

# Laravelアプリケーションのセットアップ
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
```

#### 2.3 フロントエンドのセットアップ
```bash
# Node.js依存関係のインストール
npm install

# アセットのビルド
npm run build
```

#### 2.4 開発環境での実行
```bash
# 開発サーバーの起動
composer run dev
```

### 3. 本番環境のセットアップ

#### 3.1 環境変数ファイルの準備
```bash
# .env.productionを.envにコピー
cd laravel
cp .env.production .env

# .envファイルを編集して、実際の本番環境の設定に変更
# - APP_URL: 本番環境のドメイン
# - DB_HOST: RDSエンドポイント
# - DB_DATABASE: 本番環境のデータベース名
# - DB_USERNAME: RDSマスターユーザー名
# - DB_PASSWORD: RDSマスターパスワード
nano .env  # またはお好みのエディタ
cd ..
```

#### 3.2 Docker環境での起動
```bash
# Dockerコンテナのビルドと起動（本番用）
docker-compose -f docker-compose.production.yml up -d --build

# Laravelアプリケーションのセットアップ
docker-compose -f docker-compose.production.yml exec php composer install --optimize-autoloader --no-dev
docker-compose -f docker-compose.production.yml exec php php artisan key:generate
docker-compose -f docker-compose.production.yml exec php php artisan migrate --force
```

#### 3.3 最適化
```bash
# 設定キャッシュ
docker-compose -f docker-compose.production.yml exec php php artisan config:cache

# ルートキャッシュ
docker-compose -f docker-compose.production.yml exec php php artisan route:cache

# ビューキャッシュ
docker-compose -f docker-compose.production.yml exec php php artisan view:cache
```

### 4. ファイル構成について

このプロジェクトでは、ローカル開発環境と本番環境で異なる設定ファイルを使用します：

- **ローカル開発環境**
  - `docker-compose.yml` - ローカル開発用（MySQLコンテナ、phpMyAdmin含む）
  - `laravel/.env` - ローカル開発用の環境変数

- **本番環境**
  - `docker-compose.production.yml` - 本番デプロイ用（RDSを使用、phpMyAdminなし）
  - `laravel/.env.production` - 本番環境用の環境変数テンプレート
  - `laravel/.env` - 本番環境で実際に使用する環境変数（`.env.production`からコピーして編集）

## プロジェクト構造

```
HMapp_laravel/
├── docker/                        # Docker設定ファイル
│   ├── nginx/                    # Nginx設定
│   └── php/                      # PHP-FPM設定
├── laravel/                       # Laravelアプリケーション
│   ├── app/
│   │   ├── Http/Controllers/      # コントローラー
│   │   ├── Models/               # Eloquentモデル
│   │   └── View/Components/      # Bladeコンポーネント
│   ├── database/
│   │   ├── migrations/           # データベースマイグレーション
│   │   └── seeders/             # シーダー
│   ├── resources/views/          # Bladeテンプレート
│   └── routes/                   # ルート定義
├── docker-compose.yml            # Docker Compose設定（ローカル開発用）
└── docker-compose.production.yml # Docker Compose設定（本番環境用）
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

# ローカル開発環境
docker-compose exec php php artisan pail

# 本番環境
docker-compose -f docker-compose.production.yml exec php php artisan pail
```

### データベースのリセット（ローカル開発環境のみ）
```bash
docker-compose exec php php artisan migrate:fresh --seed
```






