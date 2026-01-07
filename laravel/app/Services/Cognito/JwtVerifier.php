<?php

namespace App\Services\Cognito;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Str;

class JwtVerifier
{
    public function __construct(private JwksProvider $jwksProvider) {}

    /**
     * id_tokenまたはaccess_tokenを検証
     * @return array{claims: array, header: array}
     */
    public function verifyToken(string $jwt): array
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
        $tokenUse = $claims['token_use'] ?? null;

        if (($claims['iss'] ?? null) !== $issuer) {
            throw new \RuntimeException('Invalid issuer');
        }

        // token_useが'id'または'access'のいずれかを許可
        if (!in_array($tokenUse, ['id', 'access'])) {
            throw new \RuntimeException("Invalid token_use: {$tokenUse}. Expected 'id' or 'access'");
        }

        // id_tokenの場合: audを検証
        if ($tokenUse === 'id') {
            if (($claims['aud'] ?? null) !== $clientId) {
                throw new \RuntimeException('Invalid audience for id_token');
            }
        }

        // access_tokenの場合: client_idを検証（audの代わり）
        if ($tokenUse === 'access') {
            if (($claims['client_id'] ?? null) !== $clientId) {
                throw new \RuntimeException('Invalid client_id for access_token');
            }
        }

        if (empty($claims['sub'])) {
            throw new \RuntimeException('Missing sub');
        }

        return ['claims' => $claims, 'header' => $header];
    }

    /**
     * id_tokenを検証（後方互換性のため残す）
     * @return array{claims: array, header: array}
     */
    public function verifyIdToken(string $jwt): array
    {
        $result = $this->verifyToken($jwt);
        $tokenUse = $result['claims']['token_use'] ?? null;
        
        if ($tokenUse !== 'id') {
            throw new \RuntimeException("Expected id_token but got token_use: {$tokenUse}");
        }
        
        return $result;
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