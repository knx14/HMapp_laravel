<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AppUser;
use App\Services\Cognito\CognitoAuthException;
use App\Services\Cognito\CognitoAuthResult;
use App\Services\Cognito\CognitoAuthService;
use App\Services\Cognito\CognitoUserMissingException;
use App\Services\Cognito\CognitoWebSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public const CHALLENGE_SESSION_KEY = 'cognito_challenge';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, CognitoAuthService $cognito, CognitoWebSession $webSession): RedirectResponse
    {
        try {
            $result = $request->authenticate($cognito);
        } catch (CognitoAuthException $e) {
            if ($e->errorCode === CognitoAuthException::PASSWORD_RESET_REQUIRED) {
                return redirect()->route('password.request')
                    ->withInput(['email' => $request->email()])
                    ->with('status', 'パスワードの再設定が必要です。確認コードを送信してください。');
            }

            Log::warning('Cognito login failed', ['error_code' => $e->errorCode, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages(['email' => $e->userMessage()]);
        }

        return self::completeAuthentication($request, $result, $webSession);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, CognitoWebSession $webSession): RedirectResponse
    {
        $webSession->logout($request);

        return redirect()->route('login');
    }

    /**
     * 認証結果に応じて、ログインさせるか新パスワード設定画面へ送る。
     */
    public static function completeAuthentication(Request $request, CognitoAuthResult $result, CognitoWebSession $webSession): RedirectResponse
    {
        if ($result->requiresNewPassword()) {
            $request->session()->put(self::CHALLENGE_SESSION_KEY, [
                'username' => $result->challengeUsername,
                'session' => Crypt::encryptString((string) $result->session),
            ]);

            return redirect()->route('password.challenge');
        }

        if ($result->tokens === null) {
            Log::warning('Unsupported Cognito challenge', ['challenge' => $result->challengeName]);

            throw ValidationException::withMessages(['email' => 'このアカウントはWebからログインできません。管理者にお問い合わせください。']);
        }

        try {
            $user = $webSession->login($request, $result->tokens);
        } catch (CognitoUserMissingException) {
            throw ValidationException::withMessages(['email' => 'アカウント情報が見つかりません。管理者にお問い合わせください。']);
        } catch (\Throwable $e) {
            Log::error('Cognito id token verification failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages(['email' => 'ログイン処理でエラーが発生しました。しばらくしてから再度お試しください。']);
        }

        return redirect()->intended(self::homeUrl($user));
    }

    public static function homeUrl(AppUser $user): string
    {
        return $user->isAdmin()
            ? route('dashboard', absolute: false)
            : route('farm-management.index', absolute: false);
    }
}
