<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Farm;

class FarmPolicy
{
    /**
     * Web 画面での閲覧。管理者は全圃場（非表示の圃場を含む）、一般ユーザーは自分の表示中の圃場。
     */
    public function view(AppUser $user, Farm $farm): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->own($user, $farm) && $farm->hidden_at === null;
    }

    /**
     * 圃場の所有者か。モバイル向け API は管理者でもこの判定だけを使う。
     */
    public function own(AppUser $user, Farm $farm): bool
    {
        return (int) $farm->app_user_id === (int) $user->id;
    }

    public function update(AppUser $user, Farm $farm): bool
    {
        return $this->own($user, $farm);
    }

    public function delete(AppUser $user, Farm $farm): bool
    {
        return $this->own($user, $farm);
    }
}
