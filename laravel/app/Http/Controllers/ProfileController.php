<?php

namespace App\Http\Controllers;

use App\Services\Account\AccountSettingsService;
use App\Services\Admin\AdminRoleService;
use App\Services\Cognito\CognitoAuthException;
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
            'pendingEmail' => $request->session()->get(AccountSettingsService::PENDING_EMAIL_KEY),
        ]);
    }

    public function updateName(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], ['name' => 'ユーザーネーム']);

        try {
            $settings->updateName($request, $request->user(), $validated['name']);
        } catch (CognitoAuthException $e) {
            $this->failFromCognito($e, 'name');
        }

        return redirect()->route('profile.edit')->with('status', 'ユーザーネームを変更しました。');
    }

    public function requestEmailChange(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [], ['email' => 'メールアドレス']);

        try {
            $settings->requestEmailChange($request, $request->user(), $validated['email']);
        } catch (CognitoAuthException $e) {
            $this->failFromCognito($e, 'email');
        }

        return redirect()->route('profile.edit')->with('status', '確認コードを送信しました。新しいメールアドレスに届いたコードを入力してください。コードを入れるまでは、いまのメールアドレスでログインできます。');
    }

    public function confirmEmailChange(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [], ['code' => '確認コード']);

        try {
            $settings->confirmEmailChange($request, $request->user(), $validated['code']);
        } catch (CognitoAuthException $e) {
            $this->failFromCognito($e, 'code');
        }

        return redirect()->route('profile.edit')->with('status', 'メールアドレスを変更しました。次回からは新しいメールアドレスでログインしてください。');
    }

    public function cancelEmailChange(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        $settings->cancelEmailChange($request);

        return redirect()->route('profile.edit')->with('status', 'メールアドレスの変更を中止しました。いまのメールアドレスのままです。');
    }

    public function updatePassword(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'パスワードは8文字以上で、大文字・小文字・数字・記号をそれぞれ含めてください。',
            'password.confirmed' => '新しいパスワード（確認）が一致しません。',
        ], [
            'current_password' => '現在のパスワード',
            'password' => '新しいパスワード',
        ]);

        try {
            $settings->changePassword($request, $validated['current_password'], $validated['password']);
        } catch (CognitoAuthException $e) {
            if ($e->errorCode === CognitoAuthException::NOT_AUTHORIZED) {
                throw ValidationException::withMessages(['current_password' => '現在のパスワードが違います。']);
            }
            $this->failFromCognito($e, 'password');
        }

        return redirect()->route('profile.edit')->with('status', 'パスワードを変更しました。');
    }

    public function destroy(Request $request, AccountSettingsService $settings): RedirectResponse
    {
        try {
            $settings->deleteAccount($request, $request->user());
        } catch (CognitoAuthException $e) {
            $this->failFromCognito($e, 'account');
        }

        return redirect()->route('login')->with('status', 'アカウントを削除しました。');
    }

    /**
     * @return never
     */
    private function failFromCognito(CognitoAuthException $e, string $field): void
    {
        $message = match ($e->errorCode) {
            CognitoAuthException::ALIAS_EXISTS => 'このメールアドレスは既に使われています。',
            CognitoAuthException::CODE_MISMATCH => '確認コードが違います。',
            CognitoAuthException::EXPIRED_CODE => '確認コードの有効期限が切れています。もう一度コードを送信してください。',
            CognitoAuthException::INVALID_PASSWORD => $e->userMessage(),
            CognitoAuthException::LIMIT_EXCEEDED, CognitoAuthException::TOO_MANY_REQUESTS => '試行回数が多すぎます。しばらくしてから再度お試しください。',
            CognitoAuthException::NOT_CONFIGURED => $e->userMessage(),
            default => '保存できませんでした。しばらくしてから再度お試しください。',
        };

        throw ValidationException::withMessages([$field => $message]);
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
