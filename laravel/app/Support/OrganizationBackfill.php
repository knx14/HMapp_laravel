<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * 所属が未入力のユーザーに、登録時の ja_name を正規化して初期値として入れる。
 */
class OrganizationBackfill
{
    public function run(): void
    {
        DB::table('app_users')
            ->whereNull('organization')
            ->whereNotNull('ja_name')
            ->orderBy('id')
            ->select(['id', 'ja_name'])
            ->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    $organization = OrganizationName::normalize($user->ja_name);
                    if ($organization === null) {
                        continue;
                    }

                    DB::table('app_users')->where('id', $user->id)->update([
                        'organization' => mb_substr($organization, 0, OrganizationName::MAX_LENGTH),
                    ]);
                }
            });
    }
}
