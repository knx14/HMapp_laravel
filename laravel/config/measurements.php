<?php

return [
    // 管理者の生データ付き CSV は1行ごとに S3 を読むため、同期で待てる件数に抑える。
    'admin_export_limit' => (int) env('MEASUREMENT_ADMIN_EXPORT_LIMIT', 500),

    'per_page' => 20,

    'display_timezone' => 'Asia/Tokyo',
];
