<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUser extends Authenticatable
{
	use HasFactory, Notifiable;

	public const ROLE_USER = 'user';
	public const ROLE_ADMIN = 'admin';

	protected $table = 'app_users';

	protected $fillable = [
		'cognito_sub',
		'name',
		'email',
		'ja_name',
		'organization',
	];

	protected $hidden = [
	];

	protected $attributes = [
		'role' => self::ROLE_USER,
	];

	protected $casts = [
		'admin_granted_at' => 'datetime',
	];

	/**
	 * Cognito で認証するため、Laravel 側には remember token を持たない。
	 */
	public function getRememberTokenName()
	{
		return '';
	}

	public function isAdmin(): bool
	{
		return $this->role === self::ROLE_ADMIN;
	}

	public function hasOrganization(): bool
	{
		return $this->organization !== null && $this->organization !== '';
	}

	/**
	 * アプリユーザーが所有する農場とのリレーション
	 */
	public function farms(): HasMany
	{
		return $this->hasMany(Farm::class);
	}

	/**
	 * 管理者権限の変更履歴
	 */
	public function adminRoleEvents(): HasMany
	{
		return $this->hasMany(AdminRoleEvent::class)->latest('created_at')->latest('id');
	}
}
