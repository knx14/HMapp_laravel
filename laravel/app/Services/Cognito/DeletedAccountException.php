<?php

namespace App\Services\Cognito;

/**
 * 論理削除済みのアカウントでログインしようとしたとき。
 */
class DeletedAccountException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('このアカウントは削除されています。');
    }
}
