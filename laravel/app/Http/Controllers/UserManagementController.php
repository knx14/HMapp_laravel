<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
	public function index(Request $request)
	{
		$name = $request->query('name');
		$jaName = $request->query('ja_name');
		$cognitoSub = $request->query('cognito_sub');

		$query = AppUser::query();
		if (!empty($name)) {
			$query->where('name', 'like', '%' . $name . '%');
		}
		if (!empty($jaName)) {
			$query->where('ja_name', 'like', '%' . $jaName . '%');
		}
		if (!empty($cognitoSub)) {
			$query->where('cognito_sub', 'like', '%' . $cognitoSub . '%');
		}

		$users = $query->orderByDesc('id')->get();

		return view('user_management.index', [
			'users' => $users,
			'filters' => [
				'name' => $name ?? '',
				'ja_name' => $jaName ?? '',
				'cognito_sub' => $cognitoSub ?? '',
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
			'cognito_sub' => 'nullable|string|max:255|unique:app_users,cognito_sub',
			'name' => 'nullable|string|max:255',
			'email' => 'nullable|string|email|max:255',
			'ja_name' => 'nullable|string|max:255',
		], [
			'cognito_sub.unique' => 'このCognito Subは既に登録されています。',
			'cognito_sub.max' => 'Cognito Subは255文字以内で入力してください。',
			'name.max' => '名前は255文字以内で入力してください。',
			'email.email' => '有効なメールアドレスを入力してください。',
			'email.max' => 'メールアドレスは255文字以内で入力してください。',
			'ja_name.max' => 'JA名は255文字以内で入力してください。',
		]);

		AppUser::create([
			'cognito_sub' => $request->cognito_sub,
			'name' => $request->name,
			'email' => $request->email,
			'ja_name' => $request->ja_name,
		]);

		return redirect()->route('user-management.index')
			->with('success', 'ユーザーが正常に登録されました。');
	}
}


