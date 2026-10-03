<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 以前のセッション Cookie 名を破棄する。
 * Secure の有無が違う同名 Cookie が残ると、ログイン送信の CSRF が 419 になる。
 */
class ForgetLegacySessionCookies
{
    /** @var list<string> */
    private const LEGACY_NAMES = [
        'hmadminapp_session',
        'hmapp_session',
        'laravel_session',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $current = (string) config('session.cookie');
        $path = (string) config('session.path', '/');
        $domain = config('session.domain');
        $domain = is_string($domain) && $domain !== '' && $domain !== 'null' ? $domain : null;

        foreach (self::LEGACY_NAMES as $name) {
            if ($name === $current) {
                continue;
            }

            // いま本番に残っているのは Secure なしの Cookie。名前を変えた新しい Cookie とは別物なので、こちらを消す。
            $response->headers->clearCookie($name, $path, $domain, false);
        }

        return $response;
    }
}
