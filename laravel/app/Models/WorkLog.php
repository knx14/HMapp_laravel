<?php

namespace App\Models;

use App\Enums\WorkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'work_type',
        'work_date',
        'title',
        'detail',
        'amount_value',
        'amount_unit',
        'scope',
    ];

    protected $casts = [
        'work_date' => 'date',
        'amount_value' => 'decimal:2',
        'work_type' => WorkType::class,
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
