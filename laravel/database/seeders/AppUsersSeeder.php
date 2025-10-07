<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppUsersSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$names = [
			['surname' => '佐藤', 'given' => '太郎'],
			['surname' => '鈴木', 'given' => '花子'],
			['surname' => '高橋', 'given' => '健'],
			['surname' => '田中', 'given' => '美咲'],
			['surname' => '伊藤', 'given' => '翔'],
			['surname' => '渡辺', 'given' => '葵'],
			['surname' => '山本', 'given' => '悠真'],
			['surname' => '中村', 'given' => '結衣'],
			['surname' => '小林', 'given' => '陸'],
			['surname' => '加藤', 'given' => '咲良'],
			['surname' => '吉田', 'given' => '大和'],
			['surname' => '山田', 'given' => '楓'],
			['surname' => '佐々木', 'given' => '蓮'],
			['surname' => '山口', 'given' => '陽菜'],
			['surname' => '松本', 'given' => '蒼'],
			['surname' => '井上', 'given' => '凛'],
			['surname' => '木村', 'given' => '颯太'],
			['surname' => '林', 'given' => 'ひまり'],
			['surname' => '斎藤', 'given' => '湊'],
			['surname' => '清水', 'given' => 'さくら'],
		];

		$jaRegions = [
			'JA北海道', 'JA青森', 'JA岩手', 'JA宮城', 'JA秋田', 'JA山形', 'JA福島', 'JA茨城', 'JA栃木', 'JA群馬',
			'JA埼玉', 'JA千葉', 'JA東京', 'JA神奈川', 'JA新潟', 'JA富山', 'JA石川', 'JA福井', 'JA山梨', 'JA長野',
		];

		$now = now();
		$users = [];
		foreach ($names as $index => $name) {
			$fullName = $name['surname'] . '　' . $name['given'];
			$users[] = [
				'name' => $fullName,
				'email' => sprintf('appuser%02d@example.com', $index + 1),
				'password' => Hash::make('password'),
				'ja_name' => $jaRegions[$index % count($jaRegions)],
				'remember_token' => Str::random(60),
				'created_at' => $now,
				'updated_at' => $now,
			];
		}

		// email をユニークキーとして Upsert（既存は更新、未存在は作成）
		DB::table('app_users')->upsert(
			$users,
			['email'],
			['name', 'ja_name', 'password', 'remember_token', 'updated_at']
		);
	}
}


