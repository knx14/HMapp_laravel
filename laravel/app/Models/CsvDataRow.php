<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvDataRow extends Model
{
    protected $fillable = [
        'csv_file_id', 
        'user_id', 
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
