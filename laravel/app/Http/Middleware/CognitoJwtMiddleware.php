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
            \Log::warning('Cognito JWT auth failed: No Authorization header', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $jwt = trim($m[1]);

        \Log::info('Cognito JWT auth started', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'token_preview' => substr($jwt, 0, 50) . '...',
        ]);

        try {
            // id_tokenとaccess_tokenの両方に対応
            $result = $this->verifier->verifyToken($jwt);
            $claims = $result['claims'];
            $tokenUse = $claims['token_use'] ?? null;

            \Log::info('JWT verification successful', [
                'token_use' => $tokenUse,
                'sub' => $claims['sub'] ?? null,
                'iss' => $claims['iss'] ?? null,
            ]);

            $sub = $claims['sub'];

            // id_tokenの場合: emailとnameを取得
            // access_tokenの場合: emailとnameは含まれない可能性がある
            $email = $claims['email'] ?? null;
            $name = $claims['name'] ?? ($claims['cognito:username'] ?? null);

            $appUser = $this->resolver->resolve($sub, $email, $name);

            // Requestに注入（以降Controllerで取り出す）
            $request->attributes->set('auth_user', $appUser);

            \Log::info('User resolved successfully', [
                'user_id' => $appUser->id,
                'cognito_sub' => $appUser->cognito_sub,
            ]);

            return $next($request);
        } catch (\Throwable $e) {
            // 詳細なエラー情報をログに記録
            \Log::error('Cognito JWT auth failed', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 開発環境では詳細なエラー情報を返す
            $isDebug = config('app.debug', false);
            $response = ['message' => 'Unauthenticated'];
            
            if ($isDebug) {
                $response['error'] = $e->getMessage();
                $response['error_type'] = get_class($e);
            }

            return response()->json($response, 401);
        }
    }
}