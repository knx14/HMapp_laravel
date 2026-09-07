<?php

namespace App\Models;

use App\Support\SoilParameterUnits;
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

    public function getUnitAttribute(?string $value): ?string
    {
        $name = (string) ($this->attributes['parameter_name'] ?? '');

        return SoilParameterUnits::displayUnit($name, $value);
    }
}


