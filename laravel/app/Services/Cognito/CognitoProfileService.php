<?php

namespace App\Services\Cognito;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * 設定画面から Cognito のユーザー属性・パスワード・無効化を更新する。
 *
 * 名前の変更と無効化は管理者 API（AdminUpdateUserAttributes / AdminDisableUser）で、
 * EC2 の IAM ロールに次の権限が要る。
 * cognito-idp:AdminUpdateUserAttributes、AdminDisableUser、AdminEnableUser。
 *
 * メールアドレスの変更とパスワード変更は、ログイン中のアクセストークンで行う。
 * メールアドレスは確認コードを入れるまで確定しない。
 * ユーザー名が現在のメールアドレスと一致するときは、確認後にユーザー名も新しいメールアドレスになる。
 * 一致しないときはメールアドレスはエイリアスなので、ユーザー名はそのままにする。
 */
class CognitoProfileService
{
    private ?CognitoIdentityProviderClient $adminClient = null;

    private ?CognitoIdentityProviderClient $userClient = null;

    public function updateName(string $username, string $name): void
    {
        $this->call(fn () => $this->adminClient()->adminUpdateUserAttributes([
            'UserPoolId' => $this->userPoolId(),
            'Username' => $username,
            'UserAttributes' => [
                ['Name' => 'name', 'Value' => $name],
            ],
        ]));
    }

    public function requestEmailChange(string $accessToken, string $email): void
    {
        $this->call(fn () => $this->userClient()->updateUserAttributes([
            'AccessToken' => $accessToken,
            'UserAttributes' => [
                ['Name' => 'email', 'Value' => $email],
            ],
        ]));
    }

    public function confirmEmailChange(string $accessToken, string $code): void
    {
        $this->call(fn () => $this->userClient()->verifyUserAttribute([
            'AccessToken' => $accessToken,
            'AttributeName' => 'email',
            'Code' => $code,
        ]));
    }

    public function changePassword(string $accessToken, string $currentPassword, string $proposedPassword): void
    {
        $this->call(fn () => $this->userClient()->changePassword([
            'AccessToken' => $accessToken,
            'PreviousPassword' => $currentPassword,
            'ProposedPassword' => $proposedPassword,
        ]));
    }

    public function disableUser(string $username): void
    {
        $this->call(fn () => $this->adminClient()->adminDisableUser([
            'UserPoolId' => $this->userPoolId(),
            'Username' => $username,
        ]));
    }

    /**
     * 管理者が他ユーザーのメールアドレスを確定させる。確認コードは使わない。
     */
    public function updateEmailVerified(string $username, string $email): void
    {
        $this->call(fn () => $this->adminClient()->adminUpdateUserAttributes([
            'UserPoolId' => $this->userPoolId(),
            'Username' => $username,
            'UserAttributes' => [
                ['Name' => 'email', 'Value' => $email],
                ['Name' => 'email_verified', 'Value' => 'true'],
            ],
        ]));
    }

    /**
     * Cognito の Username を sub から引く。Admin API は sub だけでは更新できない。
     */
    public function usernameForSub(string $sub): string
    {
        $username = null;
        $this->call(function () use ($sub, &$username): void {
            $result = $this->adminClient()->listUsers([
                'UserPoolId' => $this->userPoolId(),
                'Filter' => 'sub = "'.$sub.'"',
                'Limit' => 1,
            ]);
            $username = $result['Users'][0]['Username'] ?? null;
        });

        if (! is_string($username) || $username === '') {
            throw new CognitoAuthException(CognitoAuthException::USER_NOT_FOUND, 'Cognito user was not found');
        }

        return $username;
    }

    private function call(callable $operation): void
    {
        try {
            $operation();
        } catch (CognitoIdentityProviderException $e) {
            throw new CognitoAuthException((string) ($e->getAwsErrorCode() ?? 'Unknown'), (string) ($e->getAwsErrorMessage() ?? $e->getMessage()), $e);
        } catch (\Aws\Exception\AwsException $e) {
            throw new CognitoAuthException('Unknown', $e->getMessage(), $e);
        }
    }

    private function adminClient(): CognitoIdentityProviderClient
    {
        if ($this->adminClient === null) {
            $this->userPoolId();
            $this->adminClient = new CognitoIdentityProviderClient([
                'version' => '2016-04-18',
                'region' => (string) config('cognito.region'),
                'retries' => 1,
                'http' => [
                    'connect_timeout' => 5,
                    'timeout' => 10,
                ],
            ]);
        }

        return $this->adminClient;
    }

    private function userClient(): CognitoIdentityProviderClient
    {
        if ($this->userClient === null) {
            $this->userClient = new CognitoIdentityProviderClient([
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

        return $this->userClient;
    }

    private function userPoolId(): string
    {
        $userPoolId = (string) config('cognito.user_pool_id');
        if ($userPoolId === '') {
            throw new CognitoAuthException(CognitoAuthException::NOT_CONFIGURED, 'Cognito user pool is not configured');
        }

        return $userPoolId;
    }
}
