<?php

namespace App\Services\Users;

use App\Models\AppUser;
use App\Services\Cognito\CognitoProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 管理者によるユーザー名、メールアドレスの変更と、チェックしたユーザーの論理削除。
 * Cognito の更新に失敗したときは、その項目の DB を変えない。
 */
class UserAdminService
{
    public function __construct(private CognitoProfileService $cognito) {}

    public function updateName(AppUser $user, string $name): void
    {
        $this->cognito->updateName($this->username($user), $name);
        $user->update(['name' => $name]);
    }

    public function updateEmail(AppUser $user, string $email): void
    {
        $email = Str::lower(trim($email));
        if (strcasecmp((string) $user->email, $email) === 0) {
            throw ValidationException::withMessages(['email' => 'いまのメールアドレスと同じです。']);
        }
        if (AppUser::withTrashed()->where('email', $email)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'このメールアドレスは既に使われています。']);
        }

        $this->cognito->updateEmailVerified($this->username($user), $email);
        $user->update(['email' => $email]);
    }

    /**
     * 自分自身は除く。無効化に失敗したユーザーは論理削除を戻し、そこで止める。
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(AppUser $actor, array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $targets = AppUser::query()->whereIn('id', $ids)->whereKeyNot($actor->id)->get();
        $deleted = 0;

        foreach ($targets as $user) {
            $username = $this->username($user);
            DB::transaction(function () use ($user, $username): void {
                $user->delete();
                try {
                    $this->cognito->disableUser($username);
                } catch (\Throwable $e) {
                    $user->restore();
                    throw $e;
                }
            });
            $deleted++;
        }

        return $deleted;
    }

    private function username(AppUser $user): string
    {
        $sub = (string) $user->cognito_sub;
        if ($sub === '') {
            throw ValidationException::withMessages([
                'account' => 'このユーザーは Cognito と紐づいていないため、変更できません。',
            ]);
        }

        return $this->cognito->usernameForSub($sub);
    }
}
