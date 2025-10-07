<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultValue extends Model
{
    protected $fillable = [
        'analysis_result_id',
        'parameter',
        'value',
        'unit',
    ];

    public function analysisResult(): BelongsTo
    {
        return $this->belongsTo(AnalysisResult::class);
    }
}


