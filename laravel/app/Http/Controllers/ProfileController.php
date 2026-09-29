<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * 設定画面
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * 管理者キーを照合し、一致すれば管理者権限を付与する。
     */
    public function grantAdmin(Request $request, AdminRoleService $adminRoles): RedirectResponse
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('profile.edit');
        }

        $request->validate(['admin_key' => ['required', 'string', 'max:255']], [], ['admin_key' => '管理者キー']);

        $throttle = config('admin.grant_throttle');
        $userKey = 'admin-grant:user:'.$user->id;
        $ipKey = 'admin-grant:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($userKey, $throttle['per_user'])
            || RateLimiter::tooManyAttempts($ipKey, $throttle['per_ip'])) {
            throw ValidationException::withMessages(['admin_key' => 'しばらくしてから再度お試しください。']);
        }

        $context = ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()];

        if (! $adminRoles->isGrantKeyConfigured()) {
            throw ValidationException::withMessages(['admin_key' => '管理者キーが設定されていません。管理者にお問い合わせください。']);
        }

        if (! $adminRoles->grantKeyMatches((string) $request->input('admin_key'))) {
            RateLimiter::hit($userKey, $throttle['decay_seconds']);
            RateLimiter::hit($ipKey, $throttle['decay_seconds']);
            $adminRoles->recordGrantFailure($user, $context);

            throw ValidationException::withMessages(['admin_key' => '管理者キーが違います。']);
        }

        RateLimiter::clear($userKey);
        $adminRoles->grant($user, $context);

        return redirect()->route('profile.edit')->with('status', '管理者権限を有効にしました。');
    }
}
