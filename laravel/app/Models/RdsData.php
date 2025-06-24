<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RdsData extends Model
{
    /**
     * 接続先のデータベース接続名
     */
    protected $connection = 'rds';

    /**
     * テーブル名
     */
    protected $table = 'your_table_name';

    /**
     * 主キー
     */
    protected $primaryKey = 'id';

    /**
     * タイムスタンプの自動更新を無効化
     */
    public $timestamps = false;

    /**
     * 代入可能な属性
     */
    protected $fillable = [
        'cognito_id',
        'created_at',
        // 他のカラムがあれば追加
    ];

    /**
     * 日付として扱う属性
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * ユーザーIDで検索
     */
    public function scopeByCognitoId($query, $cognitoId)
    {
        return $query->where('cognito_id', $cognitoId);
    }

    /**
     * 日付範囲で検索
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 日付で検索
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }
} 