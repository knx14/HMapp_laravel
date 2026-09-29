<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Cognito\CognitoAuthException;
use App\Services\Cognito\CognitoAuthService;
use App\Services\Cognito\CognitoWebSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cognito の NEW_PASSWORD_REQUIRED チャレンジに応える。
 */
class NewPasswordChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(AuthenticatedSessionController::CHALLENGE_SESSION_KEY)) {
            return redirect()->route('login');
        }

        return view('auth.new-password-challenge');
    }

    public function store(Request $request, CognitoAuthService $cognito, CognitoWebSession $webSession): RedirectResponse
    {
        $challenge = $request->session()->get(AuthenticatedSessionController::CHALLENGE_SESSION_KEY);
        if (! is_array($challenge)) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], ['password' => '新しいパスワード']);

        try {
            $result = $cognito->respondToNewPasswordChallenge(
                (string) $challenge['username'],
                Crypt::decryptString((string) $challenge['session']),
                (string) $request->input('password'),
            );
        } catch (CognitoAuthException $e) {
            if ($e->errorCode === CognitoAuthException::NOT_AUTHORIZED) {
                $request->session()->forget(AuthenticatedSessionController::CHALLENGE_SESSION_KEY);

                return redirect()->route('login')
                    ->withErrors(['email' => '時間が経ちすぎたため、もう一度ログインしてください。']);
            }

            throw ValidationException::withMessages(['password' => $e->userMessage()]);
        }

        $request->session()->forget(AuthenticatedSessionController::CHALLENGE_SESSION_KEY);

        return AuthenticatedSessionController::completeAuthentication($request, $result, $webSession);
    }
}
