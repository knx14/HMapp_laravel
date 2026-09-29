<?php

namespace App\Services\Cognito;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Cognito のトークンと Laravel の Web セッションを結び付ける。
 */
class CognitoWebSession
{
    private const KEY = 'cognito';

    /** 期限の少し前にリフレッシュする */
    private const REFRESH_MARGIN_SECONDS = 60;

    public function __construct(
        private JwtVerifier $jwtVerifier,
        private CognitoAuthService $cognito,
    ) {}

    /**
     * ID トークンを検証し、対応する app_users でログインさせる。
     *
     * @throws CognitoUserMissingException app_users に行が無いとき
     */
    public function login(Request $request, CognitoTokens $tokens): AppUser
    {
        $claims = $this->jwtVerifier->verifyIdToken($tokens->idToken)['claims'];
        $sub = (string) $claims['sub'];

        $user = AppUser::where('cognito_sub', $sub)->first();
        if ($user === null) {
            Log::warning('Cognito login succeeded but app_users row is missing', ['cognito_sub' => $sub]);

            throw new CognitoUserMissingException($sub);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->store($request, $tokens, (string) ($claims['cognito:username'] ?? $sub));

        return $user;
    }

    /**
     * アクセストークンが期限切れなら更新する。更新できなければ false。
     */
    public function refreshIfExpired(Request $request): bool
    {
        $data = $request->session()->get(self::KEY);
        if (! is_array($data) || empty($data['refresh_token'])) {
            return true;
        }

        if ((int) ($data['expires_at'] ?? 0) - self::REFRESH_MARGIN_SECONDS > now()->getTimestamp()) {
            return true;
        }

        try {
            $refreshToken = Crypt::decryptString($data['refresh_token']);
            $tokens = $this->cognito->refresh((string) $data['username'], $refreshToken);
            $claims = $this->jwtVerifier->verifyIdToken($tokens->idToken)['claims'];
        } catch (CognitoAuthException $e) {
            if ($e->errorCode === CognitoAuthException::NOT_AUTHORIZED || $e->errorCode === CognitoAuthException::USER_NOT_FOUND) {
                return false;
            }

            Log::warning('Cognito token refresh failed', ['error_code' => $e->errorCode]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Cognito token refresh failed', ['error' => $e->getMessage()]);

            return false;
        }

        if ((string) $claims['sub'] !== (string) Auth::guard('web')->user()?->cognito_sub) {
            return false;
        }

        $this->store($request, $tokens, (string) $data['username']);

        return true;
    }

    public function logout(Request $request): void
    {
        $data = $request->session()->get(self::KEY);
        if (is_array($data) && ! empty($data['refresh_token'])) {
            try {
                $this->cognito->revoke(Crypt::decryptString($data['refresh_token']));
            } catch (\Throwable $e) {
                Log::info('Cognito refresh token revoke failed', ['error' => $e->getMessage()]);
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function accessToken(Request $request): ?string
    {
        $data = $request->session()->get(self::KEY);
        if (! is_array($data) || empty($data['access_token'])) {
            return null;
        }

        return Crypt::decryptString($data['access_token']);
    }

    private function store(Request $request, CognitoTokens $tokens, string $username): void
    {
        $previous = $request->session()->get(self::KEY);
        $refreshToken = $tokens->refreshToken !== null
            ? Crypt::encryptString($tokens->refreshToken)
            : (is_array($previous) ? ($previous['refresh_token'] ?? null) : null);

        $request->session()->put(self::KEY, [
            'username' => $username,
            'access_token' => Crypt::encryptString($tokens->accessToken),
            'refresh_token' => $refreshToken,
            'expires_at' => now()->getTimestamp() + $tokens->expiresIn,
        ]);
    }
}
