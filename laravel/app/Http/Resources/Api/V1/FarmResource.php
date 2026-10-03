<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AppUser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class FarmResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $isOwner = $user instanceof AppUser && (int) $this->app_user_id === (int) $user->id;
        $canEdit = $user instanceof AppUser && Gate::forUser($user)->allows('operate', $this->resource);

        return [
            'id' => $this->id,
            'app_user_id' => $this->app_user_id,
            'farm_name' => $this->farm_name,
            'cultivation_method' => $this->cultivation_method,
            'crop_type' => $this->crop_type,
            'boundary_polygon' => $this->boundary_polygon,
            'owner_name' => $this->appUser?->name,
            'is_owner' => $isOwner,
            'can_edit' => $canEdit,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

