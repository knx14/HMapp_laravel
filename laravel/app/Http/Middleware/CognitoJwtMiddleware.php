<?php

namespace App\Http\Middleware;

use App\Services\Cognito\JwtVerifier;
use App\Services\Cognito\CognitoUserResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CognitoJwtMiddleware
{
    public function __construct(
        private JwtVerifier $verifier,
        private CognitoUserResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $jwt = trim($m[1]);

        try {
            $result = $this->verifier->verifyIdToken($jwt);
            $claims = $result['claims'];

            $sub = $claims['sub'];
            $email = $claims['email'] ?? null;
            $name = $claims['name'] ?? ($claims['cognito:username'] ?? null);

            $appUser = $this->resolver->resolve($sub, $email, $name);

            // Requestに注入（以降Controllerで取り出す）
            $request->attributes->set('auth_user', $appUser);

            return $next($request);
        } catch (\Throwable $e) {
            // 本番では詳細はログへ（返却は401固定）
            \Log::warning('Cognito JWT auth failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    }
}