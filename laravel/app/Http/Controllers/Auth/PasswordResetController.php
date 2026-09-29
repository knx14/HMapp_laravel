<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Cognito\CognitoAuthException;
use App\Services\Cognito\CognitoAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cognito の ForgotPassword / ConfirmForgotPassword によるパスワード再設定。
 */
class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request, CognitoAuthService $cognito): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']], [], ['email' => 'メールアドレス']);
        $email = Str::lower(trim((string) $request->input('email')));

        try {
            $cognito->forgotPassword($email);
        } catch (CognitoAuthException $e) {
            // 登録の有無を画面から推測させないため、存在しないユーザーも成功と同じ表示にする
            if ($e->errorCode !== CognitoAuthException::USER_NOT_FOUND) {
                throw ValidationException::withMessages(['email' => $e->userMessage()]);
            }
        }

        return redirect()->route('password.reset', ['email' => $email])
            ->with('status', '登録されているメールアドレスであれば、確認コードを送信しました。');
    }

    public function edit(Request $request): View
    {
        return view('auth.reset-password', ['email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request, CognitoAuthService $cognito): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'email' => 'メールアドレス',
            'code' => '確認コード',
            'password' => '新しいパスワード',
        ]);

        $email = Str::lower(trim((string) $request->input('email')));

        try {
            $cognito->confirmForgotPassword($email, trim((string) $request->input('code')), (string) $request->input('password'));
        } catch (CognitoAuthException $e) {
            $field = in_array($e->errorCode, [CognitoAuthException::CODE_MISMATCH, CognitoAuthException::EXPIRED_CODE, CognitoAuthException::USER_NOT_FOUND], true)
                ? 'code'
                : 'password';
            $message = $e->errorCode === CognitoAuthException::USER_NOT_FOUND ? '確認コードが違います。' : $e->userMessage();

            throw ValidationException::withMessages([$field => $message]);
        }

        return redirect()->route('login')->with('status', 'パスワードを再設定しました。新しいパスワードでログインしてください。');
    }
}
