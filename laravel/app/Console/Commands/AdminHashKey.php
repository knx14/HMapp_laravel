<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminHashKey extends Command
{
    protected $signature = 'admin:hash-key';

    protected $description = '管理者キーのハッシュ値（ADMIN_GRANT_KEY_HASH）を作る';

    public function handle(): int
    {
        $key = (string) $this->secret('新しい管理者キー');
        if (mb_strlen($key) < 12) {
            $this->error('管理者キーは12文字以上にしてください。');

            return self::FAILURE;
        }

        if ((string) $this->secret('もう一度入力してください') !== $key) {
            $this->error('入力が一致しません。');

            return self::FAILURE;
        }

        $this->line('.env に次の1行を設定し、php artisan config:clear（本番は config:cache）を実行してください。');
        $this->line("ADMIN_GRANT_KEY_HASH='".Hash::make($key)."'");

        return self::SUCCESS;
    }
}
