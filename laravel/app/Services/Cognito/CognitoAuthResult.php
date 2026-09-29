<?php

namespace App\Services\Cognito;

final class CognitoAuthResult
{
    public const CHALLENGE_NEW_PASSWORD_REQUIRED = 'NEW_PASSWORD_REQUIRED';

    private function __construct(
        public readonly ?CognitoTokens $tokens,
        public readonly ?string $challengeName,
        public readonly ?string $session,
        public readonly ?string $challengeUsername,
    ) {}

    public static function authenticated(CognitoTokens $tokens): self
    {
        return new self($tokens, null, null, null);
    }

    public static function challenge(string $name, string $session, string $username): self
    {
        return new self(null, $name, $session, $username);
    }

    public function requiresNewPassword(): bool
    {
        return $this->challengeName === self::CHALLENGE_NEW_PASSWORD_REQUIRED;
    }
}
