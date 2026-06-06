<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Farm\StoreFarmRequest;
use App\Http\Requests\Api\V1\Farm\UpdateFarmRequest;
use App\Http\Resources\Api\V1\FarmResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;


class FarmController extends Controller
{
    /**
     * 自分の圃場一覧を取得
     */
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        $farms = Farm::where('app_user_id', $user->id)
            ->latest()
            ->get();

        return FarmResource::collection($farms);
    }

    /**
     * 圃場を登録
     * app_user_idはcognito_subから解決されたauth_userから自動設定
     */
    public function store(StoreFarmRequest $request)
    {
        $user = $request->attributes->get('auth_user');

        // Farmモデルのfillableと同じ構成で登録
        $farm = Farm::create([
            'app_user_id' => $user->id, // cognito_subから解決されたユーザーID
            'farm_name' => $request->input('farm_name'),
            'cultivation_method' => $request->input('cultivation_method'),
            'crop_type' => $request->input('crop_type'),
            'boundary_polygon' => $request->input('boundary_polygon'),
        ]);

        return (new FarmResource($farm))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 圃場を更新
     */
    public function update(UpdateFarmRequest $request, Farm $farm)
    {
        $user = $request->attributes->get('auth_user');

        if ($farm->app_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $farm->update([
            'farm_name' => $request->input('farm_name'),
            'cultivation_method' => $request->input('cultivation_method'),
            'crop_type' => $request->input('crop_type'),
            'boundary_polygon' => $request->input('boundary_polygon'),
        ]);
        return new FarmResource($farm);
 
    }

    /**
     * 関連データのない圃場のみ削除
     */
    public function destroy(Request $request, Farm $farm): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($farm->app_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($farm->uploads()->exists() || (Schema::hasTable('work_logs') && $farm->workLogs()->exists())) {
            return response()->json([
                'message' => 'この圃場には測定データまたは作業記録が存在するため削除できません。',
            ], 422);
        }

        $farm->delete();

        return response()->json(['message' => 'deleted']);
    }
}

