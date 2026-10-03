<?php

namespace App\Services\Users;

use App\Models\AppUser;
use Illuminate\Support\Collection;

/**
 * ユーザー一覧の CSV。測定データと同じく UTF-8 BOM、CRLF、日時は日本時間。
 */
class UserCsvExporter
{
    /**
     * @return list<string>
     */
    public function header(): array
    {
        return ['Cognito sub', 'ユーザー名', 'メールアドレス', '所属', '登録日', '更新日'];
    }

    /**
     * @param  resource  $out
     * @param  Collection<int, AppUser>  $users
     */
    public function write($out, Collection $users): void
    {
        fwrite($out, "\xEF\xBB\xBF");
        $this->putRow($out, $this->header());

        foreach ($users as $user) {
            $this->putRow($out, [
                (string) $user->cognito_sub,
                (string) $user->name,
                (string) $user->email,
                (string) ($user->organization ?? ''),
                $this->timestamp($user->created_at),
                $this->timestamp($user->updated_at),
            ]);
        }
    }

    private function timestamp(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return $value->copy()->timezone(config('measurements.display_timezone'))->format('Y-m-d H:i:s');
    }

    /**
     * @param  resource  $out
     * @param  list<string>  $row
     */
    private function putRow($out, array $row): void
    {
        fputcsv($out, $row, ',', '"', '\\', "\r\n");
    }
}
