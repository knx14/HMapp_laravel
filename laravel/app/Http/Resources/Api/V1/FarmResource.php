<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class FarmResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'app_user_id' => $this->app_user_id,
            'farm_name' => $this->farm_name,
            'cultivation_method' => $this->cultivation_method,
            'crop_type' => $this->crop_type,
            'boundary_polygon' => $this->boundary_polygon,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

