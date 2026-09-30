<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 所属が未入力のユーザーは、入力が済むまで所属入力画面以外を使えない。
 */
class EnsureOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof AppUser || $user->hasOrganization()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'organization_required'], 403);
        }

        return redirect()->route('organization.edit');
    }
}
