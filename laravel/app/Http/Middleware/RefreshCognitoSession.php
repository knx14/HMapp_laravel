<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use App\Services\Cognito\CognitoWebSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cognito のアクセストークンが切れていれば更新し、更新できなければ（パスワード変更・無効化など）ログアウトさせる。
 */
class RefreshCognitoSession
{
    public function __construct(private CognitoWebSession $webSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sessionUserId = $request->session()->get(Auth::guard('web')->getName());
        if ($sessionUserId !== null) {
            $account = AppUser::withTrashed()->find($sessionUserId);
            if ($account?->trashed()) {
                $this->webSession->logout($request);

                if ($request->expectsJson()) {
                    abort(401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'このアカウントは削除されています。',
                ]);
            }
        }

        if ($request->user('web') !== null && ! $this->webSession->refreshIfExpired($request)) {
            $this->webSession->logout($request);

            if ($request->expectsJson()) {
                abort(401);
            }

            return redirect()->route('login')->withErrors(['email' => 'もう一度ログインしてください。']);
        }

        return $next($request);
    }
}
