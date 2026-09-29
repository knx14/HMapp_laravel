<?php

return [
    // 管理者キーの bcrypt ハッシュ値。`php artisan admin:hash-key` で作る。
    'grant_key_hash' => env('ADMIN_GRANT_KEY_HASH'),

    'grant_throttle' => [
        'per_user' => 5,
        'per_ip' => 10,
        'decay_seconds' => 600,
    ],
];
