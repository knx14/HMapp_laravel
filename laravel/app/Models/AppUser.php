<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUser extends Authenticatable
{
	use HasFactory, Notifiable;

	protected $table = 'app_users';

	protected $fillable = [
		'cognito_sub',
		'name',
		'email',
		'ja_name',
	];

	protected $hidden = [
	];

	/**
	 * アプリユーザーが所有する農場とのリレーション
	 */
	public function farms(): HasMany
	{
		return $this->hasMany(Farm::class);
	}
}


