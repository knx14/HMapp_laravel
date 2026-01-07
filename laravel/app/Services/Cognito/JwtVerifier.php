<?php

namespace App\Services\Cognito;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Str;

class JwtVerifier
{
    public function __construct(private JwksProvider $jwksProvider) {}

    /**
     * @return array{claims: array, header: array}
     */
    public function verifyIdToken(string $jwt): array
    {
        // JWT構造チェック
        if (substr_count($jwt, '.') !== 2) {
            throw new \RuntimeException('Invalid JWT format');
        }

        [$h64, $p64, $s64] = explode('.', $jwt);

        $header = json_decode($this->b64urlDecode($h64), true) ?? [];
        if (empty($header['kid'])) {
            throw new \RuntimeException('Missing kid');
        }

        $jwks = $this->jwksProvider->getJwks();
        $keySet = JWK::parseKeySet($jwks);

        // decode() が署名検証 + exp検証（ライブラリ側）を行う
        $decoded = JWT::decode($jwt, $keySet);
        $claims = (array) $decoded;

        // 追加のclaims検証
        $issuer = config('cognito.issuer');
        $clientId = config('cognito.client_id');

        if (($claims['iss'] ?? null) !== $issuer) {
            throw new \RuntimeException('Invalid issuer');
        }
        if (($claims['aud'] ?? null) !== $clientId) {
            throw new \RuntimeException('Invalid audience');
        }
        if (($claims['token_use'] ?? null) !== 'id') {
            throw new \RuntimeException('Invalid token_use');
        }
        if (empty($claims['sub'])) {
            throw new \RuntimeException('Missing sub');
        }

        return ['claims' => $claims, 'header' => $header];
    }

    private function b64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}