<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'app_user_id',
        'farm_name',
        'cultivation_method',
        'crop_type',
        'boundary_polygon',
    ];

    protected $casts = [
        'boundary_polygon' => 'array',
    ];

    /**
     * 農場を所有するアプリユーザーとのリレーション
     */
    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    /**
     * 農場のアップロードデータとのリレーション
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }
}
