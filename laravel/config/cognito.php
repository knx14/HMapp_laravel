<?php

return [
    'issuer' => env('COGNITO_ISSUER'),
    'region' => env('COGNITO_REGION', env('AWS_DEFAULT_REGION', 'ap-northeast-1')),
    'user_pool_id' => env('COGNITO_USER_POOL_ID'),

    // モバイルアプリ用のアプリクライアント
    'client_id' => env('COGNITO_CLIENT_ID'),

    // Web ログイン用のアプリクライアント（USER_PASSWORD_AUTH、シークレットあり）
    'web_client_id' => env('COGNITO_WEB_CLIENT_ID'),
    'web_client_secret' => env('COGNITO_WEB_CLIENT_SECRET'),

    // JWT の aud / client_id として受け付けるクライアント
    'client_ids' => array_values(array_filter([
        env('COGNITO_CLIENT_ID'),
        env('COGNITO_WEB_CLIENT_ID'),
    ])),

    'jwks_url' => env('COGNITO_ISSUER').'/.well-known/jwks.json',
    'jwks_cache_ttl_seconds' => (int) env('COGNITO_JWKS_CACHE_TTL_SECONDS', 21600),
];
