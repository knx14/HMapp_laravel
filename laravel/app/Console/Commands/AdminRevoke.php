<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use App\Services\Admin\AdminRoleService;
use Illuminate\Console\Command;

class AdminRevoke extends Command
{
    protected $signature = 'admin:revoke {email? : 一般ユーザーに戻すユーザーのメールアドレス} {--sub= : メールアドレスが重複するときに Cognito Sub で指定する}';

    protected $description = '指定したユーザーを一般ユーザーに戻す';

    public function handle(AdminRoleService $adminRoles): int
    {
        $sub = $this->option('sub');
        $email = $this->argument('email');

        if ($sub) {
            $users = AppUser::where('cognito_sub', $sub)->get();
        } elseif ($email) {
            $users = AppUser::where('email', $email)->get();
        } else {
            $this->error('メールアドレスか --sub= を指定してください。');

            return self::FAILURE;
        }

        if ($users->isEmpty()) {
            $this->error('ユーザーが見つかりません。');

            return self::FAILURE;
        }

        if ($users->count() > 1) {
            $this->error('同じメールアドレスのユーザーが複数います。--sub= で Cognito Sub を指定してください。');
            $this->table(['ID', 'Cognito Sub', '名前', '権限'], $users->map(fn (AppUser $u) => [$u->id, $u->cognito_sub, $u->name, $u->role])->all());

            return self::FAILURE;
        }

        $user = $users->first();
        if (! $user->isAdmin()) {
            $this->info("{$user->email} は管理者ではありません。");

            return self::SUCCESS;
        }

        $adminRoles->revoke($user, null);
        $this->info("{$user->email} を一般ユーザーに戻しました。");

        return self::SUCCESS;
    }
}
