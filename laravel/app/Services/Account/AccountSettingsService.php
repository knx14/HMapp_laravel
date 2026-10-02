<?php

namespace App\Services\Account;

use App\Models\AppUser;
use App\Services\Cognito\CognitoProfileService;
use App\Services\Cognito\CognitoWebSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 設定画面のユーザー情報・パスワード・アカウント削除。
 * Cognito の更新に失敗したときは DB を変えない。
 */
class AccountSettingsService
{
    public const PENDING_EMAIL_KEY = 'profile_pending_email';

    public function __construct(
        private CognitoProfileService $cognito,
        private CognitoWebSession $webSession,
    ) {}

    public function updateName(Request $request, AppUser $user, string $name): void
    {
        $this->cognito->updateName($this->requireUsername($request, 'name'), $name);
        $user->update(['name' => $name]);
    }

    public function requestEmailChange(Request $request, AppUser $user, string $email): void
    {
        $email = Str::lower(trim($email));
        if (strcasecmp((string) $user->email, $email) === 0) {
            throw ValidationException::withMessages(['email' => 'いまのメールアドレスと同じです。']);
        }
        if (AppUser::withTrashed()->where('email', $email)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'このメールアドレスは既に使われています。']);
        }

        $this->cognito->requestEmailChange($this->requireAccessToken($request, 'email'), $email);
        $request->session()->put(self::PENDING_EMAIL_KEY, $email);
    }

    public function confirmEmailChange(Request $request, AppUser $user, string $code): void
    {
        $email = $request->session()->get(self::PENDING_EMAIL_KEY);
        if (! is_string($email) || $email === '') {
            throw ValidationException::withMessages(['code' => '先に新しいメールアドレスを入力してください。']);
        }

        $this->cognito->confirmEmailChange($this->requireAccessToken($request, 'code'), $code);

        $previousEmail = (string) $user->email;
        $username = $this->webSession->username($request);
        $user->update(['email' => $email]);
        if ($username !== null && strcasecmp($username, $previousEmail) === 0) {
            $this->webSession->updateUsername($request, $email);
        }
        $request->session()->forget(self::PENDING_EMAIL_KEY);
    }

    public function cancelEmailChange(Request $request): void
    {
        $request->session()->forget(self::PENDING_EMAIL_KEY);
    }

    public function changePassword(Request $request, string $currentPassword, string $proposedPassword): void
    {
        $this->cognito->changePassword(
            $this->requireAccessToken($request, 'current_password'),
            $currentPassword,
            $proposedPassword,
        );
    }

    /**
     * 論理削除して Cognito ユーザーを無効化する。Cognito のユーザー自体は消さない。
     * 無効化に失敗したときは論理削除を戻す。
     */
    public function deleteAccount(Request $request, AppUser $user): void
    {
        if ($user->isAdmin() && ! AppUser::query()->where('role', AppUser::ROLE_ADMIN)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'account' => '最後の管理者はアカウントを削除できません。',
            ]);
        }

        $username = $this->requireUsername($request, 'account');

        DB::transaction(function () use ($user, $username): void {
            $user->delete();
            try {
                $this->cognito->disableUser($username);
            } catch (\Throwable $e) {
                $user->restore();
                throw $e;
            }
        });

        $this->webSession->logout($request);
    }

    private function requireUsername(Request $request, string $field): string
    {
        $username = $this->webSession->username($request);
        if ($username === null || $username === '') {
            throw ValidationException::withMessages([
                $field => 'ログインし直してからもう一度お試しください。',
            ]);
        }

        return $username;
    }

    private function requireAccessToken(Request $request, string $field): string
    {
        $token = $this->webSession->accessToken($request);
        if ($token === null || $token === '') {
            throw ValidationException::withMessages([
                $field => 'ログインし直してからもう一度お試しください。',
            ]);
        }

        return $token;
    }
}
