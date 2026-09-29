<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\Admin\AdminRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
	public function index(Request $request)
	{
		$name = $request->query('name');
		$jaName = $request->query('ja_name');
		$cognitoSub = $request->query('cognito_sub');
		$role = $request->query('role') === AppUser::ROLE_ADMIN ? AppUser::ROLE_ADMIN : '';

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
		if ($role !== '') {
			$query->where('role', $role);
		}

		$users = $query->orderByDesc('id')->paginate(20);

		return view('user_management.index', [
			'users' => $users,
			'filters' => [
				'name' => $name ?? '',
				'ja_name' => $jaName ?? '',
				'cognito_sub' => $cognitoSub ?? '',
				'role' => $role,
			],
		]);
	}

	public function show(AppUser $user): View
	{
		return view('user_management.show', [
			'user' => $user,
			'roleEvents' => $user->adminRoleEvents()->with('actor')->limit(50)->get(),
		]);
	}

	/**
	 * 管理者を一般ユーザーに戻す。自分自身は外せない。
	 */
	public function revokeAdmin(Request $request, AppUser $user, AdminRoleService $adminRoles): RedirectResponse
	{
		if ($user->is($request->user())) {
			return redirect()->route('user-management.show', $user)
				->withErrors(['role' => '自分自身の管理者権限は外せません。']);
		}

		if (!$user->isAdmin()) {
			return redirect()->route('user-management.show', $user);
		}

		$adminRoles->revoke($user, $request->user(), [
			'ip_address' => $request->ip(),
			'user_agent' => $request->userAgent(),
		]);

		return redirect()->route('user-management.show', $user)
			->with('success', '一般ユーザーに戻しました。');
	}
}
