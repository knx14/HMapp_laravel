<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'farm_id',
        'file_path',
        'measurement_date',
    ];

    protected $casts = [
        'measurement_date' => 'date',
    ];

    /**
     * 農場とのリレーション
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * このアップロードに紐づく分析結果
     */
    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

}
