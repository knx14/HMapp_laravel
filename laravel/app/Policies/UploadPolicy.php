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

    public function update(AppUser $user, Upload $upload): bool
    {
        return $this->own($user, $upload);
    }

    public function delete(AppUser $user, Upload $upload): bool
    {
        return $this->own($user, $upload);
    }
}
