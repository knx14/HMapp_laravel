<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Support\OrganizationName;
use App\Models\Farm;

class FarmPolicy
{
    /**
     * Web 画面での閲覧。管理者は全圃場（非表示の圃場を含む）。
     * 一般ユーザーは自分の表示中の圃場と、所属が一致するメンバーの表示中の圃場。
     */
    public function view(AppUser $user, Farm $farm): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->visibleToMember($user, $farm);
    }

    /**
     * モバイル API の閲覧。管理者でも、所属が違う圃場は開かない。
     */
    public function visibleToMember(AppUser $user, Farm $farm): bool
    {
        if ($farm->hidden_at !== null) {
            return false;
        }

        if ($this->own($user, $farm)) {
            return true;
        }

        return $this->sharesOrganization($user, $farm);
    }

    /**
     * 圃場の所有者か。モバイル向け API は管理者でもこの判定だけを使う。
     */
    public function own(AppUser $user, Farm $farm): bool
    {
        return (int) $farm->app_user_id === (int) $user->id;
    }

    /**
     * Web 画面での編集・削除・作業記録。管理者は全圃場。
     * 一般ユーザーは自分の表示中の圃場と、所属が一致するメンバーの表示中の圃場。
     */
    public function manage(AppUser $user, Farm $farm): bool
    {
        return $user->isAdmin() || $this->visibleToMember($user, $farm);
    }

    /**
     * 所有者の変更（別ユーザーへの移管）は管理者だけ。
     */
    public function changeOwner(AppUser $user, Farm $farm): bool
    {
        return $user->isAdmin();
    }

    /**
     * モバイル API の更新・削除、測定と作業記録の追加・変更。
     * 管理者でも、自分の圃場か同じ所属の表示中の圃場だけ。
     */
    public function update(AppUser $user, Farm $farm): bool
    {
        return $this->operate($user, $farm);
    }

    public function delete(AppUser $user, Farm $farm): bool
    {
        return $this->operate($user, $farm);
    }

    /**
     * 自分の圃場（非表示を含む）か、同じ所属の表示中の圃場。
     */
    public function operate(AppUser $user, Farm $farm): bool
    {
        return $this->own($user, $farm) || $this->visibleToMember($user, $farm);
    }

    /**
     * 所属名が一致するメンバーの圃場か。空の所属と、論理削除した所有者は共有しない。
     */
    private function sharesOrganization(AppUser $user, Farm $farm): bool
    {
        $organization = OrganizationName::normalize($user->organization);
        if ($organization === null) {
            return false;
        }

        $owner = $farm->relationLoaded('appUser') ? $farm->appUser : $farm->appUser()->first();
        if ($owner === null || $owner->trashed()) {
            return false;
        }

        return OrganizationName::normalize($owner->organization) === $organization
            && (int) $owner->id !== (int) $user->id;
    }
}
