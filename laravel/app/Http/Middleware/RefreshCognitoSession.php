<?php

namespace App\Http\Middleware;

use App\Services\Cognito\CognitoWebSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cognito のアクセストークンが切れていれば更新し、更新できなければ（パスワード変更・無効化など）ログアウトさせる。
 */
class RefreshCognitoSession
{
    public function __construct(private CognitoWebSession $webSession) {}

    public function handle(Request $request, Closure $next): Response
    {
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
