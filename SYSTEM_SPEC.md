# HMapp_laravel System Specification

## 1. 文書の目的

本書は `HMapp_laravel` リポジトリに実装されているシステム構成、アプリケーション機能、Web API、認証、データモデル、外部連携を、提出用仕様書として参照できるよう整理したものである。  
記載内容は、主に以下の実装ファイルを一次情報として整理している。

- `docker-compose.yml`
- `docker-compose.production.yml`
- `docker/php/Dockerfile`
- `docker/php/Dockerfile.production`
- `docker/nginx/default.conf`
- `docker/nginx/default.production.conf`
- `laravel/routes/*.php`
- `laravel/app/**/*.php`
- `laravel/database/migrations/*.php`
- `laravel/config/*.php`
- `README.md`
- `.github/workflows/deploy-backend.yml`

本書は「意図された仕様」ではなく、**現時点の実装から読み取れる事実ベースの仕様**を優先している。そのため、実装上の不整合や注意点も末尾に併記する。

## 2. システム概要

HMapp は、農業データの収集、管理、分析結果の登録、可視化を行う Laravel ベースの Web システムである。機能は大きく次の 2 系統に分かれる。

1. **管理画面系機能**
   - Laravel Breeze ベースのセッション認証でログインする Web 管理 UI
   - ユーザー管理、圃場管理、アップロード管理、推定結果閲覧、結果入力
2. **モバイル/外部クライアント向け API**
   - AWS Cognito JWT を Bearer トークンとして受け取る JSON API
   - 自ユーザー情報取得/更新、圃場一覧/登録/更新、結果サマリ/地図/API 提供

システムの中心ドメインは以下である。

- `AppUser`: モバイルアプリ利用者
- `Farm`: 圃場
- `Upload`: 計測・アップロード単位のメタ情報
- `AnalysisResult`: 計測地点
- `ResultValue`: 計測地点ごとの分析値
- `User`: 管理画面ログイン用ユーザー

## 3. 技術スタック

### 3.1 バックエンド

- PHP `^8.2`
- Laravel `^12.0`
- `firebase/php-jwt` `^6.0`
- Eloquent ORM
- Blade テンプレート

### 3.2 フロントエンド

- Vite
- Tailwind CSS
- Alpine.js
- Axios

### 3.3 開発補助/テスト

- Laravel Breeze
- Pest
- Laravel Pint
- Laravel Pail
- Laravel Sail が `require-dev` に存在するが、実運用上はカスタム Docker 構成が主である

## 4. リポジトリ構成

- `docker/`
  - Docker イメージ、Nginx 設定
- `laravel/`
  - Laravel アプリケーション本体
- `.github/workflows/`
  - GitHub Actions によるデプロイ定義
- `README.md`
  - セットアップおよび運用手順

Laravel 配下の主要構成:

- `app/Http/Controllers/`: Web/API コントローラ
- `app/Http/Middleware/`: 認証ミドルウェア
- `app/Http/Requests/`: API バリデーション
- `app/Http/Resources/`: API レスポンス整形
- `app/Models/`: ドメインモデル
- `app/Services/`: 認証・集計ロジック
- `routes/`: ルート定義
- `resources/views/`: 管理画面 Blade
- `database/migrations/`: DB スキーマ定義
- `config/`: 環境設定

## 5. 実行基盤・Docker 仕様

### 5.1 ローカル開発用 Compose

対象ファイル: `docker-compose.yml`

#### サービス構成

1. `nginx`
   - イメージ: `nginx:latest`
   - コンテナ名: `laravel-nginx`
   - 公開ポート: `80:80`
   - マウント:
     - `./laravel:/var/www/html`
     - `./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf`
   - 依存: `php`

2. `php`
   - ビルド元: `docker/php/Dockerfile`
   - コンテナ名: `laravel-php`
   - マウント:
     - `./laravel:/var/www/html`
   - DNS:
     - `8.8.8.8`
     - `1.1.1.1`
   - sysctl:
     - `net.ipv6.conf.all.disable_ipv6=1`
     - `net.ipv6.conf.default.disable_ipv6=1`

3. `db`
   - イメージ: `mysql:8.0`
   - コンテナ名: `laravel-db`
   - 公開ポート: `3306:3306`
   - 環境変数:
     - `MYSQL_ROOT_PASSWORD=root`
     - `MYSQL_DATABASE=laravel`
     - `MYSQL_USER=laraveluser`
     - `MYSQL_PASSWORD=laravelpass`
   - 永続化:
     - `mysql_data:/var/lib/mysql`

4. `phpmyadmin`
   - イメージ: `phpmyadmin/phpmyadmin`
   - コンテナ名: `laravel-phpmyadmin`
   - 公開ポート: `8081:80`
   - 接続先:
     - `PMA_HOST=db`
     - `MYSQL_ROOT_PASSWORD=root`

#### ネットワーク

- 単一ネットワーク `laravel_network`

#### 注意点

- `volumes:` 配下の `mysql_data:` 宣言がコメントアウトされており、Compose 実行環境によっては扱いの確認が必要
- `.env.example` は SQLite を初期値にしているため、Docker MySQL を使う場合は `.env` を明示的に MySQL 用へ切り替える前提となる

### 5.2 本番用 Compose

対象ファイル: `docker-compose.production.yml`

#### サービス構成

1. `nginx`
   - イメージ: `nginx:latest`
   - 公開ポート: `80:80`
   - `restart: always`
   - タイムゾーン: `TZ=Asia/Tokyo`
   - マウント:
     - `./laravel:/var/www/html`
     - `./docker/nginx/default.production.conf:/etc/nginx/conf.d/default.conf`
     - `./certbot/www:/var/www/certbot:ro`
     - `/etc/letsencrypt:/etc/letsencrypt:ro`

2. `php`
   - ビルド元: `docker/php/Dockerfile.production`
   - `restart: always`
   - タイムゾーン: `TZ=Asia/Tokyo`
   - マウント:
     - `./laravel:/var/www/html`

#### 本番環境の特徴

- DB コンテナは含まれず、README の記載どおり AWS RDS 利用前提
- `certbot` 用ディレクトリと `/etc/letsencrypt` を前提とした TLS 証明書配備構成
- `laravel` ディレクトリをホストからマウントするため、イメージ内のコードはホスト側配置により上書きされる

### 5.3 PHP コンテナ仕様

#### 開発用 `docker/php/Dockerfile`

- ベースイメージ: `php:8.2-fpm-alpine`
- 導入拡張:
  - `gd`
  - `pdo`
  - `pdo_mysql`
  - `bcmath`
  - `mbstring`
- 補助パッケージ:
  - `zip`
  - `unzip`
  - `curl`
  - `git`
- Composer バイナリを別イメージからコピー
- 作業ディレクトリ: `/var/www/html`
- 公開ポート: `9000`
- 起動コマンド: `php-fpm`

#### 本番用 `docker/php/Dockerfile.production`

- マルチステージビルド
  1. `node:20-alpine`
     - `npm ci`
     - `npm run build`
  2. `php:8.2-fpm-alpine`
     - PHP 拡張導入
     - `composer install --optimize-autoloader --no-dev`
     - `public/build` を Node ステージからコピー
- 公開ポート: `9000`
- 起動コマンド: `php-fpm`

### 5.4 Nginx 仕様

#### 開発用 `docker/nginx/default.conf`

- `listen 80`
- `server_name localhost`
- `root /var/www/html/public`
- PHP は `fastcgi_pass php:9000`
- Laravel のフロントコントローラへ `try_files` でフォールバック

#### 本番用 `docker/nginx/default.production.conf`

- バーチャルホスト:
  - `hm-admin.com`
  - `www.hm-admin.com`
  - `api.hm-admin.com`
- 共通仕様:
  - `client_max_body_size 100M`
  - PHP 実行先 `php:9000`
  - `/.well-known/acme-challenge/` を `alias /var/www/certbot/.well-known/acme-challenge/` へマッピング
  - 静的アセットは 1 年キャッシュ

## 6. デプロイ仕様

対象ファイル: `.github/workflows/deploy-backend.yml`

### 6.1 ビルド

- `main` ブランチ push または手動実行で起動
- AWS 認証後、ECR に以下を push
  - リポジトリ: `laravel-admin-app`
  - タグ: `latest`
- ビルドコマンド:
  - `docker build -t ... -f docker/php/Dockerfile.production ./laravel`

### 6.2 デプロイ

- `appleboy/ssh-action` で EC2 に SSH 接続
- EC2 上で実行:
  1. ECR ログイン
  2. `docker pull`
  3. `docker compose -f docker-compose.production.yml down`
  4. `docker compose -f docker-compose.production.yml up -d`
  5. `php artisan migrate --force`
  6. `config/cache/route/view` の clear および cache
  7. `docker compose cp php:/var/www/html/public/. ./laravel/public/`

### 6.3 デプロイ前提

- AWS Secrets の登録が必須
- EC2 側に `EC2_APP_DIR` 配下のリポジトリ配置が必要
- `docker-compose.production.yml` と `laravel/.env` は EC2 上で参照される

## 7. 環境変数・設定

### 7.1 Laravel 基本設定

対象ファイル: `laravel/.env.example`

- デフォルト DB 接続: `sqlite`
- セッション: `SESSION_DRIVER=database`
- キュー: `QUEUE_CONNECTION=database`
- キャッシュ: `CACHE_STORE=database`
- ファイルシステム: `FILESYSTEM_DISK=local`
- AWS S3 用変数:
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_DEFAULT_REGION`
  - `AWS_BUCKET`
  - `AWS_ENDPOINT`
  - `AWS_USE_PATH_STYLE_ENDPOINT`

### 7.2 Cognito 設定

対象ファイル: `laravel/config/cognito.php`

- `COGNITO_ISSUER`
- `COGNITO_CLIENT_ID`
- `COGNITO_JWKS_CACHE_TTL_SECONDS` 省略時 `21600`
- JWKS URL は `COGNITO_ISSUER/.well-known/jwks.json`

### 7.3 ファイルシステム

対象ファイル: `laravel/config/filesystems.php`

- `local`
- `public`
- `s3`

実装上、CSV ダウンロードで `Storage::disk('s3')` が直接使用されるため、S3 設定は実運用で必須である。

## 8. アプリケーションアーキテクチャ

### 8.1 認証は 2 系統存在する

1. **管理画面**
   - `App\Models\User`
   - Laravel 標準 `web` ガード
   - Breeze ベースのログイン/登録/パスワード更新

2. **モバイル API**
   - `App\Models\AppUser`
   - `Authorization: Bearer <JWT>`
   - AWS Cognito JWT をミドルウェアで検証
   - 検証後のユーザーは `request->attributes['auth_user']` に格納

### 8.2 レイヤ構成

- ルーティング: `routes/*.php`
- 認証/認可: ミドルウェア
- バリデーション: FormRequest または Controller 内 Validator
- ビジネスロジック:
  - Cognito 認証関連は `app/Services/Cognito/*`
  - 結果集計は `app/Services/Results/ResultsAggregationService.php`
- データアクセス: Eloquent を直接利用
- 画面表示: Blade

リポジトリ/ユースケース層は導入されていない。

## 9. ドメインモデル仕様

### 9.1 `app_users`

モデル: `App\Models\AppUser`

主なカラム:

- `id`
- `cognito_sub` `unique`
- `name` nullable
- `email` nullable
- `ja_name` nullable
- `created_at`
- `updated_at`

役割:

- Cognito `sub` と 1 対 1 に近い形でアプリ利用者を識別する
- `farms` を複数所有できる

### 9.2 `farms`

モデル: `App\Models\Farm`

主なカラム:

- `id`
- `app_user_id` FK -> `app_users.id`
- `farm_name`
- `cultivation_method` nullable
- `crop_type` nullable
- `boundary_polygon` JSON nullable
- timestamps

役割:

- 圃場の基本属性を保持
- 境界線ポリゴンを保持
- `uploads` を複数持つ

### 9.3 `uploads`

モデル: `App\Models\Upload`

主なカラム:

- `id`
- `farm_id` FK -> `farms.id`
- `file_path` unique
- `measurement_date` nullable
- `measurement_parameters` JSON nullable
- `note1` nullable
- `note2` nullable
- `cultivation_type` nullable
- `status` enum
  - `uploaded`
  - `processing`
  - `completed`
  - `exec_error`
- timestamps

役割:

- アップロード単位のメタ情報
- 実体ファイルは S3 上のオブジェクトキーで管理する想定
- `AnalysisResult` と 1:1

### 9.4 `analysis_results`

モデル: `App\Models\AnalysisResult`

主なカラム:

- `id`
- `upload_id` FK -> `uploads.id` + unique
- `sensor_info`
- `latitude` decimal nullable
- `longitude` decimal nullable
- timestamps

役割:

- 1 アップロードに対して 1 計測地点を持つ
- `result_values` を複数持つ

### 9.5 `result_values`

モデル: `App\Models\ResultValue`

主なカラム:

- `id`
- `analysis_result_id` FK -> `analysis_results.id`
- `parameter_name`
- `parameter_value`
- `unit`
- timestamps

制約:

- `(analysis_result_id, parameter_name)` 複合 unique

役割:

- 計測地点に対する個別分析値を保持
- 実装上、`CEC` が主要集計対象として扱われる

### 9.6 管理画面ユーザー `users`

モデル: `App\Models\User`

主なカラム:

- `name`
- `email`
- `password`
- `remember_token`
- `email_verified_at`

## 10. 状態遷移仕様

`Upload` は結果入力業務フローの状態を持つ。

1. `uploaded`
   - アップロード登録直後
   - まだ `AnalysisResult` 未登録
2. `processing`
   - `AnalysisResult` 登録済み
   - `ResultValue` 入力待ち
3. `completed`
   - `ResultValue` 保存完了
   - 集計対象 API に含まれる
4. `exec_error`
   - 実装上の定数/選択肢として存在
   - 状態遷移処理は明示実装なし

## 11. 認証・JWT 仕様

### 11.1 ミドルウェア

対象ファイル:

- `laravel/bootstrap/app.php`
- `laravel/app/Http/Middleware/CognitoJwtMiddleware.php`

エイリアス:

- `cognito.jwt`

処理概要:

1. `Authorization` ヘッダを取得
2. `Bearer <token>` 形式でなければ `401 {"message":"Unauthenticated"}`
3. `JwtVerifier::verifyToken()` で JWT を検証
4. `CognitoUserResolver::resolve()` で `AppUser` を解決
5. 成功時 `request->attributes['auth_user']` に格納
6. 失敗時 `401`
7. `APP_DEBUG=true` の場合のみ `error` と `error_type` をレスポンスに付加

### 11.2 JWT 検証ロジック

対象ファイル: `laravel/app/Services/Cognito/JwtVerifier.php`

検証内容:

- JWT が 3 セグメント構造であること
- ヘッダ内 `kid` 必須
- JWKS を取得し署名検証
- `iss == config('cognito.issuer')`
- `token_use` は `id` または `access`
- `id` トークン時は `aud == client_id`
- `access` トークン時は `client_id == client_id`
- `sub` 必須

### 11.3 JWKS 取得仕様

対象ファイル: `laravel/app/Services/Cognito/JwksProvider.php`

- URL: `config('cognito.jwks_url')`
- HTTP タイムアウト: 5 秒
- キャッシュキー: `cognito_jwks`
- TTL: `config('cognito.jwks_cache_ttl_seconds')`

### 11.4 アプリユーザー解決

対象ファイル: `laravel/app/Services/Cognito/CognitoUserResolver.php`

- `AppUser::where('cognito_sub', $sub)->first()`
- 見つからない場合は例外
- 新規登録はアプリ外処理、コメント上は Lambda 連携前提

### 11.5 認証方式の補足

- `routes/api.php` に `auth:sanctum` のサンプル `/api/user` がある
- ただし `composer.json` の `require` に `laravel/sanctum` は存在しない
- 実質的な API 認証方式は Cognito JWT が主仕様である

## 12. Web 管理画面仕様

対象ファイル: `laravel/routes/web.php`

### 12.1 公開ページ

- `GET /`
  - `auth.auth` ビューを返却

### 12.2 セッション認証必須ページ

- `GET /dashboard`
  - ダッシュボード表示
- `GET/PATCH/DELETE /profile`
  - プロフィール編集

### 12.3 管理機能

#### ユーザー管理

- `GET /users`
  - `AppUser` の一覧/検索
  - 検索条件:
    - `name`
    - `ja_name`
    - `cognito_sub`

#### 圃場管理

- `GET /farms`
  - 圃場一覧
  - 検索条件:
    - `cultivation_method`
    - `crop_type`
- `GET /farms/create`
  - 圃場登録画面
- `POST /farms`
  - 圃場登録

圃場登録仕様:

- `owner_name` から `AppUser` を検索
- GPS 4 点必須、最大 8 点
- 座標を時計回りにソートして保存
- 保存形式は以下:

```json
{
  "boundary_polygon": [
    [lat, lng],
    [lat, lng]
  ]
}
```

#### アップロード管理

- `GET /uploads`
  - 一覧/検索
- `GET /uploads/create`
  - 登録画面
- `POST /uploads`
  - 登録
- `GET /uploads/download?path=...`
  - S3 上の CSV ストリームダウンロード

アップロード登録仕様:

- `farm_id` 必須
- `file_path` 一意
- `measurement_date` nullable
- `status` 必須
- `measurement_parameters` は JSON 文字列として受け取り、デコードして保存

#### 推定結果・結果入力

- `GET /estimation-results`
  - 圃場一覧
- `GET /estimation-results/farms/{farm}`
  - 圃場単位の日付一覧と入力待ちアップロード表示
- `GET /estimation-results/farms/{farm}/uploads/{upload}`
  - CEC マップ表示
- `GET /estimation-results/farms/{farm}/input`
  - 測定点入力画面
- `POST /estimation-results/farms/{farm}/analysis-result`
  - 測定点登録
- `GET /estimation-results/farms/{farm}/analysis-results/{analysisResult}/input-value`
  - 分析値入力画面
- `POST /estimation-results/farms/{farm}/analysis-results/{analysisResult}/result-value`
  - 分析値保存

結果入力仕様:

- `AnalysisResult` 登録時に座標が圃場ポリゴン内にあるかをレイキャスティングで検証
- `AnalysisResult` 登録成功後、`Upload.status = processing`
- `ResultValue` 保存時、既存値を削除して再登録
- `ResultValue` 保存成功後、`Upload.status = completed`

### 12.4 管理画面ダッシュボード

対象ファイル: `laravel/app/Http/Controllers/DashboardController.php`

表示指標:

- `app_users` 件数
- `uploads` 件数
- `uploads.status = completed` 件数

## 13. Web API 仕様

## 13.1 ルート構成

対象ファイル:

- `laravel/routes/api.php`
- `laravel/routes/api/v1.php`

API は大きく以下に分かれる。

1. Cognito JWT 必須 API
2. 公開 API
3. `web.php` 側に生えている `/api/...` 形式の JSON エンドポイント

### 13.2 Cognito JWT 必須 API

#### `GET /api/v1/me`

- 認証済み `AppUser` 情報を返す
- レスポンス:
  - `id`
  - `cognito_sub`
  - `name`
  - `email`
  - `ja_name`

#### `PUT|PATCH /api/v1/me`

- 自分のユーザー情報を更新
- バリデーション:
  - `name`: nullable string max255
  - `email`: nullable email max255
  - `ja_name`: nullable string max255
- 未送信項目は既存値を保持

#### `GET /api/v1/farms`

- 自分の圃場一覧を返す
- レスポンスは `FarmResource` コレクション

#### `POST /api/v1/farms`

- 自分の圃場を作成
- `app_user_id` は認証ユーザーから自動設定
- バリデーション:
  - `farm_name`: required
  - `cultivation_method`: nullable
  - `crop_type`: nullable
  - `boundary_polygon`: required array min:4
  - `boundary_polygon.*.lat`: numeric between -90,90
  - `boundary_polygon.*.lng`: numeric between -180,180
- 正常時 201

#### `PUT|PATCH /api/v1/farms/{farm}`

- 自分の圃場のみ更新可能
- 他ユーザー圃場に対しては `403 {"message":"Forbidden"}`
- バリデーションは作成時と同等

#### `GET /api/results/latest`

- 自ユーザーの圃場ごとの最新 `completed` 計測日を返す
- `latest_measurement_date` が存在する圃場のみ返却
- レスポンス要素:
  - `farm_id`
  - `farm_name`
  - `latest_measurement_date`
  - `cec_stats`
  - `summary_text`

#### `GET /api/farms/with-latest-result`

- 自ユーザーの全圃場を返却
- 最新結果がある場合のみ `latest_result` を内包
- 結果がない圃場は `latest_result: null`

#### `GET /api/farms/{farmId}/results/dates`

- 指定圃場の `completed` 計測日一覧を返す
- 圃場所有者チェックあり
- 各日付について以下を返す:
  - `measurement_date`
  - `cec_stats`
  - `summary_text`

#### `GET /api/farms/{farmId}/results/map?date=YYYY-MM-DD`

- 指定日付の地図表示用データを返す
- レスポンス:
  - `farm`
    - `farm_id`
    - `farm_name`
    - `boundary_polygon`
  - `measurement_date`
  - `points`

#### `GET /api/farms/{farmId}/results/map-diff?date=YYYY-MM-DD`

- 指定日と直前計測日の差分を返す
- 直前日が存在しない場合:
  - `404 {"message":"previous_not_found"}`
- 地点マッチング仕様:
  - 当日地点を基準に前回地点から最近傍を選択
  - Haversine 距離 `<= 3.0m` の場合のみ同一点とみなす
- レスポンス:
  - `farm`
  - `measurement_date`
  - `previous_measurement_date`
  - `points`
    - `point_id`
    - `lat`
    - `lng`
    - `current_values`
    - `previous_values`
    - `diff_values`

### 13.3 公開 API

#### `GET /api/analysis/summary`

- 圃場と測定日単位で集約したサマリ
- `uploads`、`farms`、`app_users` を結合して返す
- 項目:
  - `farm_id`
  - `farm_name`
  - `owner_name`
  - `date`
  - `upload_id` (`MAX(uploads.id)`)

#### `GET /api/uploads/{uploadId}/analysis-data`

- 指定アップロードの境界線と分析点を返す想定
- 項目:
  - `boundary_polygon`
  - `analysis_points`

注意:

- 実装では `Upload::with(['analysisResults.resultValues'])` を参照しているが、`Upload` モデルに定義されているのは `analysisResult()` 単数であり、不整合がある

#### `GET /api/farms/{farmId}/boundary`

- 圃場境界線を返す
- 認証不要
- CORS ヘッダを付与
- レスポンス:
  - 成功時 `success: true` + `data`
  - 失敗時 `error`, `message`

### 13.4 `web.php` 上の JSON API

#### `GET /api/farms/{farmId}/boundary`

- `web.php` にも同じパスが存在
- `api.php` と重複定義されているため、最終的なルーティング解決には注意が必要

#### `GET /api/farms/{farmId}/measurements`

- 指定圃場に紐づく全 `AnalysisResult` を返す
- `AnalysisResult -> ResultValue` を展開して返却
- CORS ヘッダ付き

## 14. API レスポンス整形仕様

### 14.1 `FarmResource`

返却項目:

- `id`
- `app_user_id`
- `farm_name`
- `cultivation_method`
- `crop_type`
- `boundary_polygon`
- `created_at` ISO 8601
- `updated_at` ISO 8601

### 14.2 CEC 集計仕様

対象ファイル: `laravel/app/Services/Results/ResultsAggregationService.php`

#### `computeCecStats()`

- 対象パラメータは `parameter == 'CEC'`
- 返却:
  - `avg`
  - `min`
  - `max`
  - `count_points`
- `count_points` は地点数固定
- CEC が 1 件もない場合:
  - `avg = null`
  - `min = null`
  - `max = null`

#### `computeSummaryText()`

- `min/max` が null の場合: `データ不足`
- `max - min < 2.0`: `ばらつき小`
- `max - min < 5.0`: `ややばらつき`
- それ以外: `ばらつき大`

### 14.3 ポリゴン正規化

対象ファイル:

- `ResultsAggregationService::normalizeBoundaryPolygon()`
- `EstimationResultsController::normalizePolygon()`

対応フォーマット:

1. `[{lat, lng}, ...]`
2. `[[lat, lng], ...]`
3. `{"boundary_polygon": [[lat, lng], ...]}`

## 15. バリデーション仕様

### 15.1 FormRequest 採用箇所

- `UpdateMeRequest`
- `StoreFarmRequest`
- `UpdateFarmRequest`

### 15.2 Controller 内バリデーション

- `FarmManagementController::store()`
- `UploadManagementController::store()`
- `EstimationResultsController::storeAnalysisResult()`
- `EstimationResultsController::storeResultValue()`

### 15.3 日付パラメータ検証

対象ファイル: `ResultsApiController::requireDateParam()`

- クエリ `date` は `YYYY-MM-DD` 形式必須
- `Carbon::createFromFormat()` で厳密検証
- 不正時 `422 {"message":"invalid_date"}`

## 16. 外部連携仕様

### 16.1 AWS Cognito

- JWT 発行元
- `issuer` と `client_id` により検証
- JWKS で署名検証

### 16.2 AWS S3

- アップロード済み CSV のダウンロード元
- `Storage::disk('s3')` を使用
- `download` API はストリーム転送

### 16.3 AWS ECR / EC2

- GitHub Actions から ECR にイメージ格納
- EC2 上で Compose 再起動

## 17. バッチ・キュー・通知仕様

実装調査結果:

- `app/Jobs` なし
- `app/Notifications` なし
- スケジューラ実装なし
- `routes/console.php` はサンプルの `inspire` のみ

補足:

- `composer dev` スクリプトには `queue:listen` が含まれるが、実ジョブ定義は存在しない

## 18. 画面実装一覧

主な Blade ビュー:

- `resources/views/auth/*`
- `resources/views/dashboard.blade.php`
- `resources/views/user_management/index.blade.php`
- `resources/views/farm_management/index.blade.php`
- `resources/views/farm_management/create.blade.php`
- `resources/views/upload_management/index.blade.php`
- `resources/views/upload_management/create.blade.php`
- `resources/views/estimation_results/index.blade.php`
- `resources/views/estimation_results/farm_dates.blade.php`
- `resources/views/estimation_results/cec_map.blade.php`
- `resources/views/estimation_results/input_result.blade.php`
- `resources/views/estimation_results/input_result_value.blade.php`
- `resources/views/profile/edit.blade.php`

## 19. テスト実装

確認できた Feature テスト:

- `tests/Feature/Api/ResultsMapDiffPreviousNotFoundTest.php`
  - `map-diff` API で前回日付がない場合の `404 previous_not_found` を検証
- `tests/Feature/Auth/PasswordUpdateTest.php`
- `tests/Feature/ExampleTest.php`

業務仕様を網羅するテストは限定的であり、仕様の主たる根拠は実装コードである。

## 20. 既知の実装上の注意点

### 20.1 DB 設定の初期値不整合

- `.env.example` は SQLite を既定値にしている
- `docker-compose.yml` は MySQL コンテナを提供している
- Docker 開発時は `.env` の DB 接続切替が必要


### 20.3 `analysis-data` API のリレーション不整合

- `FarmController::analysisData()` は `analysisResults` を eager load している
- `Upload` モデルの定義は `analysisResult()` 単数
- 実行時挙動の確認が必要

### 20.4 `/api/farms/{farmId}/boundary` の重複定義

- `routes/api.php`
- `routes/web.php`

同一パスが複数箇所にあり、保守上の注意が必要

### 20.5 本番 Compose とイメージ内容の二重管理

- `Dockerfile.production` はアプリコードをイメージに同梱する
- 一方で本番 Compose は `./laravel:/var/www/html` をマウントする
- ホスト上ファイルが優先されるため、デプロイ運用の理解が必要

### 20.6 ボリューム宣言

- `docker-compose.yml` では `mysql_data` のトップレベル定義がコメントアウトされている

## 21. まとめ

本システムは、**Laravel 管理画面**と**Cognito JWT 認証の API**を併せ持つ農業データ管理基盤である。  
業務上の主軸は `AppUser -> Farm -> Upload -> AnalysisResult -> ResultValue` のデータ連鎖であり、特に `Upload.status` を中心とした結果入力業務フローと、`CEC` を中心にした結果集計 API が特徴となる。  
運用面では Docker/Nginx/PHP-FPM による実行、AWS Cognito/S3/ECR/EC2 との連携が前提であり、環境変数と本番ファイル配置の整合が重要である。
