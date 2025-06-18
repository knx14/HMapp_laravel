<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvDataRow extends Model
{
    // 使用するテーブル名（Laravelの命名規則と異なる場合に明示）
    protected $table = 'csv_data_rows';

    protected $fillable = [
        'csv_file_id', 
        'date_time',
        'total_count', 
        'memo', 
        'count1', 
        'count2',
        'command', 
        'frequency', 
        'c_real', 
        'c_imag',
    ];
    // タイムスタンプの自動更新を有効にする（created_at, updated_at）
    public $timestamps = true;
}
