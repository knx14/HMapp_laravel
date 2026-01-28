<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;

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

		$users = $query->orderByDesc('id')->paginate(20);

		return view('user_management.index', [
			'users' => $users,
			'filters' => [
				'name' => $name ?? '',
				'ja_name' => $jaName ?? '',
				'cognito_sub' => $cognitoSub ?? '',
			],
		]);
	}
}


