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
     * 自分の測定か、同じ所属のメンバーの測定。地点の調整と削除に使う。
     */
    public function operate(AppUser $user, Upload $upload): bool
    {
        return $upload->farm !== null && $this->farms->operate($user, $upload->farm);
    }

    /**
     * 地点の調整。Web の管理者は全測定、それ以外は自分の測定と同じ所属の測定。
     */
    public function update(AppUser $user, Upload $upload): bool
    {
        return $user->isAdmin() || $this->operate($user, $upload);
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
        return $user->isAdmin() || $this->operate($user, $upload);
    }
}
