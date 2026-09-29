<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRoleEvent extends Model
{
    public const ACTION_GRANTED = 'granted';
    public const ACTION_REVOKED = 'revoked';
    public const ACTION_GRANT_FAILED = 'grant_failed';

    public const UPDATED_AT = null;

    protected $fillable = [
        'app_user_id',
        'action',
        'actor_app_user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'actor_app_user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_GRANTED => '管理者権限を付与',
            self::ACTION_REVOKED => '管理者権限を剥奪',
            self::ACTION_GRANT_FAILED => '管理者キーの不一致',
            default => $this->action,
        };
    }
}
