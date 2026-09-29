<?php

namespace App\Services\Cognito;

final class CognitoTokens
{
    public function __construct(
        public readonly string $idToken,
        public readonly string $accessToken,
        public readonly ?string $refreshToken,
        public readonly int $expiresIn,
    ) {}
}
