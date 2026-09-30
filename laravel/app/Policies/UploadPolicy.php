<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Upload;

/**
 * 測定の権限は、測定が属する圃場の権限に従う。
 */
class UploadPolicy
{
    public function __construct(private FarmPolicy $farms) {}

    public function view(AppUser $user, Upload $upload): bool
    {
        return $upload->farm !== null && $this->farms->view($user, $upload->farm);
    }

    public function own(AppUser $user, Upload $upload): bool
    {
        return $upload->farm !== null && $this->farms->own($user, $upload->farm);
    }

    /**
     * Web 画面での地点の調整。モバイル向け API は own だけを使う。
     */
    public function update(AppUser $user, Upload $upload): bool
    {
        return $user->isAdmin() || $this->own($user, $upload);
    }

    /**
     * 推定値・測定日時・測定番号の編集は管理者だけ。
     */
    public function edit(AppUser $user, Upload $upload): bool
    {
        return $user->isAdmin();
    }

    public function delete(AppUser $user, Upload $upload): bool
    {
        return $user->isAdmin() || $this->own($user, $upload);
    }
}
