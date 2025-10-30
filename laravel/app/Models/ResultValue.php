<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultValue extends Model
{
    protected $fillable = [
        'analysis_result_id',
        'parameter_name',
        'parameter_value',
        'unit',
    ];

    protected $casts = [
        'parameter_value' => 'float',
    ];

    public function analysisResult(): BelongsTo
    {
        return $this->belongsTo(AnalysisResult::class);
    }
}


