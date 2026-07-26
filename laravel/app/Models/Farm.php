<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'farm_name',
        'cultivation_method',
        'crop_type',
        'boundary_polygon',
        'hidden_at',
    ];

    protected $casts = [
        'boundary_polygon' => 'array',
        'hidden_at' => 'datetime',
    ];

    /**
     * モバイルアプリ上で表示可能な圃場に限定する。
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

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

    /**
     * 圃場に測定データが存在するか判定する。
     */
    public function hasMeasurementData(): bool
    {
        return $this->uploads()->exists();
    }

    /**
     * 圃場をモバイルアプリ上で非表示にする。
     */
    public function hide(): void
    {
        $this->update(['hidden_at' => now()]);
    }

    /**
     * 非表示にした圃場を再表示する。
     */
    public function unhide(): void
    {
        $this->update(['hidden_at' => null]);
    }

    /**
     * 農場の作業記録とのリレーション
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class)
            ->orderBy('work_date', 'desc');
    }

    /**
     * boundary_polygon の有無で仮登録かどうかを判定する。
     */
    public function isProvisional(): bool
    {
        return empty($this->boundary_polygon);
    }
}
