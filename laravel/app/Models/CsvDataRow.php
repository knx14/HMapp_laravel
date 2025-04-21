<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvDataRow extends Model
{
    protected $fillable = [
        'csv_file_id', 'user_id', 'date_time',
        'total_count', 'memo', 'count1', 'count2',
        'command', 'data_type', 'data_values',
    ];
}
