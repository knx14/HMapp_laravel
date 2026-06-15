<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalysisResultController extends Controller
{
    /**
     * PATCH /api/v1/results/{analysisResult}/location
     * 測定地点の緯度経度を更新する。
     */
    public function updateLocation(Request $request, AnalysisResult $analysisResult): JsonResponse
    {
        if (!$this->ownsAnalysisResult($request, $analysisResult)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $analysisResult->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'message' => 'updated',
            'data' => $analysisResult->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/results/{analysisResult}
     * 測定地点とその推定値を削除する。
     */
    public function destroy(Request $request, AnalysisResult $analysisResult): JsonResponse
    {
        if (!$this->ownsAnalysisResult($request, $analysisResult)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::transaction(function () use ($analysisResult): void {
            $analysisResult->resultValues()->delete();
            $analysisResult->delete();
        });

        return response()->json(['message' => 'deleted']);
    }

    private function ownsAnalysisResult(Request $request, AnalysisResult $analysisResult): bool
    {
        $user = $this->authUser($request);

        $analysisResult->loadMissing('upload.farm');

        return $analysisResult->upload !== null
            && $analysisResult->upload->farm !== null
            && (int) $analysisResult->upload->farm->app_user_id === (int) $user->id;
    }

    private function authUser(Request $request): AppUser
    {
        $user = $request->attributes->get('auth_user');
        if (!$user instanceof AppUser) {
            abort(response()->json(['message' => 'Unauthenticated'], 401));
        }

        return $user;
    }
}
