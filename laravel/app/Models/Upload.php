<?php

namespace App\Models;

use App\Casts\JstDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 測定は物理削除しない。analysis_results / result_values / S3 の生データは削除後も残す。
 */
class Upload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_id',
        'file_path',
        'measurement_date',
        'measured_at',
        'measurement_number',
        'measurement_parameters',
        'note1',
        'note2',
        'cultivation_type',
        'status',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'measured_at' => JstDateTime::class,
        'measurement_number' => 'integer',
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

    public function isManualEntry(): bool
    {
        return ($this->measurement_parameters['manual_entry'] ?? false) === true;
    }

    /**
     * 画面・CSV に出す測定日時（日本時間）。時刻の無い測定（手動入力など）は日付だけを返す。
     */
    public function measuredAtLabel(): string
    {
        return $this->measured_at?->format('Y-m-d H:i')
            ?? $this->measurement_date?->format('Y-m-d')
            ?? '';
    }
}
