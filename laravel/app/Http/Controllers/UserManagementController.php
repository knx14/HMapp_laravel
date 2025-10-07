<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
	public function index(Request $request)
	{
		$name = $request->query('name');
		$jaName = $request->query('ja_name');

		$query = AppUser::query();
		if (!empty($name)) {
			$query->where('name', 'like', '%' . $name . '%');
		}
		if (!empty($jaName)) {
			$query->where('ja_name', 'like', '%' . $jaName . '%');
		}

		$users = $query->orderByDesc('id')->get();

		return view('user_management.index', [
			'users' => $users,
			'filters' => [
				'name' => $name ?? '',
				'ja_name' => $jaName ?? '',
			],
		]);
	}

	/**
	 * 新規ユーザー登録画面を表示
	 */
	public function create()
	{
		return view('user_management.create');
	}

	/**
	 * 新規ユーザーを登録
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|string|email|max:255|unique:app_users',
			'ja_name' => 'required|string|max:255',
		], [
			'name.required' => '名前は必須です。',
			'name.max' => '名前は255文字以内で入力してください。',
			'email.required' => 'メールアドレスは必須です。',
			'email.email' => '有効なメールアドレスを入力してください。',
			'email.unique' => 'このメールアドレスは既に登録されています。',
			'ja_name.required' => 'JA名は必須です。',
			'ja_name.max' => 'JA名は255文字以内で入力してください。',
		]);

		AppUser::create([
			'name' => $request->name,
			'email' => $request->email,
			'ja_name' => $request->ja_name,
			'password' => Hash::make('password123'), // デフォルトパスワード
		]);

		return redirect()->route('user-management.index')
			->with('success', 'ユーザーが正常に登録されました。');
	}
}


