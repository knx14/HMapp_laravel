<?php

namespace App\Services\Cognito;

class CognitoUserMissingException extends \RuntimeException
{
    public function __construct(public readonly string $cognitoSub)
    {
        parent::__construct('Cognito user has no app_users row');
    }
}
