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
        'measurement_parameters',
        'note1',
        'note2',
        'cultivation_type',
        'status',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'measurement_parameters' => 'array',
    ];

    public const STATUS_UPLOADED   = 'uploaded';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_EXEC_ERROR = 'exec_error';

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
    public function analysisResult(): HasOne
    {
        return $this->hasOne(AnalysisResult::class);
    }

}
