<?php

namespace App\Services\Cognito;

use App\Models\AppUser;

class CognitoUserResolver
{
    public function resolve(string $sub, ?string $email = null, ?string $name = null): AppUser
    {
        $user = AppUser::where('cognito_sub', $sub)->first();
        if ($user) {
            return $user;
        }

        // ユーザーが見つからない場合は例外を投げる
        // （Flutterアプリで新規登録時にLambda経由でapp_usersテーブルに登録されるため、通常は存在するはず）
        throw new \RuntimeException('User not found in app_users table. Please register first.');
    }
}