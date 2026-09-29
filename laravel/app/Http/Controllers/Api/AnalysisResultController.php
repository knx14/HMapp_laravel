<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnalysisResultController extends Controller
{
    /**
     * PATCH /api/v1/results/{analysisResult}/location
     * 測定地点の緯度経度を更新する。
     */
    public function updateLocation(Request $request, AnalysisResult $analysisResult): JsonResponse
    {
        $this->ensureUploadNotDeleted($analysisResult);

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
     * 測定そのもの（upload）を論理削除する。推定結果と S3 の生データは残す。
     */
    public function destroy(Request $request, AnalysisResult $analysisResult): JsonResponse
    {
        $this->ensureUploadNotDeleted($analysisResult);

        if (!$this->ownsAnalysisResult($request, $analysisResult)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $analysisResult->upload->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * 論理削除した測定の推定結果は存在しないものとして扱う。
     */
    private function ensureUploadNotDeleted(AnalysisResult $analysisResult): void
    {
        $upload = $analysisResult->upload()->withTrashed()->first();

        if ($upload !== null && $upload->trashed()) {
            abort(response()->json(['message' => 'Not Found'], 404));
        }
    }

    private function ownsAnalysisResult(Request $request, AnalysisResult $analysisResult): bool
    {
        $user = $this->authUser($request);

        $analysisResult->loadMissing('upload.farm');

        return $analysisResult->upload !== null
            && Gate::forUser($user)->allows('own', $analysisResult->upload);
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
