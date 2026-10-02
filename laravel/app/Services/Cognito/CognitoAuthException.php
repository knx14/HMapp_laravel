<?php

namespace App\Services\Cognito;

class CognitoAuthException extends \RuntimeException
{
    public const NOT_AUTHORIZED = 'NotAuthorizedException';
    public const USER_NOT_FOUND = 'UserNotFoundException';
    public const USER_NOT_CONFIRMED = 'UserNotConfirmedException';
    public const PASSWORD_RESET_REQUIRED = 'PasswordResetRequiredException';
    public const INVALID_PASSWORD = 'InvalidPasswordException';
    public const CODE_MISMATCH = 'CodeMismatchException';
    public const EXPIRED_CODE = 'ExpiredCodeException';
    public const LIMIT_EXCEEDED = 'LimitExceededException';
    public const TOO_MANY_REQUESTS = 'TooManyRequestsException';
    public const NOT_CONFIGURED = 'NotConfigured';
    public const ALIAS_EXISTS = 'AliasExistsException';
    public const INVALID_PARAMETER = 'InvalidParameterException';

    public function __construct(public readonly string $errorCode, string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : $errorCode, 0, $previous);
    }

    /**
     * パスワード・ユーザーの誤りとして同じ文言で扱うべきエラーか。
     */
    public function isInvalidCredentials(): bool
    {
        return in_array($this->errorCode, [self::NOT_AUTHORIZED, self::USER_NOT_FOUND], true);
    }

    public function userMessage(): string
    {
        return match ($this->errorCode) {
            self::NOT_AUTHORIZED, self::USER_NOT_FOUND => 'メールアドレスまたはパスワードが違います。',
            self::USER_NOT_CONFIRMED => 'モバイルアプリで確認コードの入力を完了してください。',
            self::INVALID_PASSWORD => 'パスワードは8文字以上で、大文字・小文字・数字・記号をそれぞれ含めてください。',
            self::CODE_MISMATCH => '確認コードが違います。',
            self::EXPIRED_CODE => '確認コードの有効期限が切れています。もう一度コードを送信してください。',
            self::LIMIT_EXCEEDED, self::TOO_MANY_REQUESTS => '試行回数が多すぎます。しばらくしてから再度お試しください。',
            self::ALIAS_EXISTS => 'このメールアドレスは既に使われています。',
            self::INVALID_PARAMETER => '入力内容を確認してください。',
            self::NOT_CONFIGURED => 'ログイン機能の設定が完了していません。管理者にお問い合わせください。',
            default => 'ログイン処理でエラーが発生しました。しばらくしてから再度お試しください。',
        };
    }
}
