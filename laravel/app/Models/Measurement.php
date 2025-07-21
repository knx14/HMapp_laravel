<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    //マスアサインメント保護の設定
    protected $fillable = [
        'cognito_id',
        's3_file_path',
        'location_info',
        'serial_no',
        'predicted_cec',
    ];

    // データベースから取得した値を自動的に型変換するための設定
    protected $casts = [
        'predicted_cec' => 'decimal:5', // 'predicted_cec' を小数点以下5桁のdecimal型にキャスト
        'created_at' => 'datetime',    // 'created_at' をCarbon (datetime) オブジェクトにキャスト
        'updated_at' => 'datetime',    // 'updated_at' をCarbon (datetime) オブジェクトにキャスト
    ];
}
