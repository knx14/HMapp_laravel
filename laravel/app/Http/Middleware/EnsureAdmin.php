<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * app_users.role = 'admin' のときだけ通す。role はセッションに持たず毎回 DB の値を見る。
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof AppUser || ! $user->isAdmin()) {
            abort(403, '管理者のみ利用できます。');
        }

        return $next($request);
    }
}
