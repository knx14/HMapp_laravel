<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use Illuminate\Console\Command;

class AdminList extends Command
{
    protected $signature = 'admin:list';

    protected $description = '管理者の一覧を表示する';

    public function handle(): int
    {
        $admins = AppUser::where('role', AppUser::ROLE_ADMIN)->orderBy('id')->get();

        if ($admins->isEmpty()) {
            $this->info('管理者はいません。');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Cognito Sub', '名前', 'メールアドレス', '管理者になった日時'],
            $admins->map(fn (AppUser $user) => [
                $user->id,
                $user->cognito_sub,
                $user->name,
                $user->email,
                $user->admin_granted_at?->format('Y-m-d H:i:s') ?? '-',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
