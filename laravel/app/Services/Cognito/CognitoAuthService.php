<?php

namespace App\Services\Cognito;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Result;

/**
 * Web ログイン用アプリクライアントで Cognito の認証 API を呼ぶ。
 * ここで使う API は AWS の認証情報を必要としない（クライアントシークレットで署名する）。
 */
class CognitoAuthService
{
    private ?CognitoIdentityProviderClient $client = null;

    public function authenticate(string $email, string $password): CognitoAuthResult
    {
        $result = $this->call('initiateAuth', [
            'AuthFlow' => 'USER_PASSWORD_AUTH',
            'ClientId' => $this->clientId(),
            'AuthParameters' => [
                'USERNAME' => $email,
                'PASSWORD' => $password,
                'SECRET_HASH' => $this->secretHash($email),
            ],
        ]);

        return $this->toAuthResult($result, $email);
    }

    public function respondToNewPasswordChallenge(string $username, string $session, string $newPassword): CognitoAuthResult
    {
        $result = $this->call('respondToAuthChallenge', [
            'ChallengeName' => CognitoAuthResult::CHALLENGE_NEW_PASSWORD_REQUIRED,
            'ClientId' => $this->clientId(),
            'Session' => $session,
            'ChallengeResponses' => [
                'USERNAME' => $username,
                'NEW_PASSWORD' => $newPassword,
                'SECRET_HASH' => $this->secretHash($username),
            ],
        ]);

        return $this->toAuthResult($result, $username);
    }

    /**
     * @param  string  $username  Cognito のユーザー名（ID トークンの cognito:username）
     */
    public function refresh(string $username, string $refreshToken): CognitoTokens
    {
        $result = $this->call('initiateAuth', [
            'AuthFlow' => 'REFRESH_TOKEN_AUTH',
            'ClientId' => $this->clientId(),
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
                'SECRET_HASH' => $this->secretHash($username),
            ],
        ]);

        $auth = $result->get('AuthenticationResult') ?? [];

        return new CognitoTokens(
            idToken: (string) ($auth['IdToken'] ?? ''),
            accessToken: (string) ($auth['AccessToken'] ?? ''),
            refreshToken: $refreshToken,
            expiresIn: (int) ($auth['ExpiresIn'] ?? 3600),
        );
    }

    public function revoke(string $refreshToken): void
    {
        $this->call('revokeToken', [
            'ClientId' => $this->clientId(),
            'ClientSecret' => $this->clientSecret(),
            'Token' => $refreshToken,
        ]);
    }

    public function forgotPassword(string $email): void
    {
        $this->call('forgotPassword', [
            'ClientId' => $this->clientId(),
            'Username' => $email,
            'SecretHash' => $this->secretHash($email),
        ]);
    }

    public function confirmForgotPassword(string $email, string $code, string $newPassword): void
    {
        $this->call('confirmForgotPassword', [
            'ClientId' => $this->clientId(),
            'Username' => $email,
            'ConfirmationCode' => $code,
            'Password' => $newPassword,
            'SecretHash' => $this->secretHash($email),
        ]);
    }

    private function toAuthResult(Result $result, string $username): CognitoAuthResult
    {
        $challenge = $result->get('ChallengeName');
        if ($challenge !== null) {
            $params = $result->get('ChallengeParameters') ?? [];

            return CognitoAuthResult::challenge(
                (string) $challenge,
                (string) $result->get('Session'),
                (string) ($params['USER_ID_FOR_SRP'] ?? $username),
            );
        }

        $auth = $result->get('AuthenticationResult') ?? [];

        return CognitoAuthResult::authenticated(new CognitoTokens(
            idToken: (string) ($auth['IdToken'] ?? ''),
            accessToken: (string) ($auth['AccessToken'] ?? ''),
            refreshToken: isset($auth['RefreshToken']) ? (string) $auth['RefreshToken'] : null,
            expiresIn: (int) ($auth['ExpiresIn'] ?? 3600),
        ));
    }

    private function call(string $operation, array $args): Result
    {
        try {
            return $this->client()->{$operation}($args);
        } catch (CognitoIdentityProviderException $e) {
            throw new CognitoAuthException((string) ($e->getAwsErrorCode() ?? 'Unknown'), (string) ($e->getAwsErrorMessage() ?? $e->getMessage()), $e);
        } catch (\Aws\Exception\AwsException $e) {
            throw new CognitoAuthException('Unknown', $e->getMessage(), $e);
        }
    }

    private function client(): CognitoIdentityProviderClient
    {
        if ($this->client === null) {
            $this->clientId();
            $this->client = new CognitoIdentityProviderClient([
                'version' => '2016-04-18',
                'region' => (string) config('cognito.region'),
                'credentials' => false,
                'retries' => 1,
                'http' => [
                    'connect_timeout' => 5,
                    'timeout' => 10,
                ],
            ]);
        }

        return $this->client;
    }

    private function secretHash(string $username): string
    {
        return base64_encode(hash_hmac('sha256', $username.$this->clientId(), $this->clientSecret(), true));
    }

    private function clientId(): string
    {
        $clientId = (string) config('cognito.web_client_id');
        if ($clientId === '' || (string) config('cognito.web_client_secret') === '') {
            throw new CognitoAuthException(CognitoAuthException::NOT_CONFIGURED, 'Cognito web client is not configured');
        }

        return $clientId;
    }

    private function clientSecret(): string
    {
        return (string) config('cognito.web_client_secret');
    }
}
