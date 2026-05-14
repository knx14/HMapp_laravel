<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkLog\StoreWorkLogRequest;
use App\Http\Requests\Api\V1\WorkLog\UpdateWorkLogRequest;
use App\Models\Farm;
use App\Models\WorkLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkLogController extends Controller
{
    public function index(Request $request, Farm $farm): JsonResponse
    {
        if (!$this->ownsFarm($request, $farm)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $farm->workLogs()->get(),
        ]);
    }

    public function store(StoreWorkLogRequest $request, Farm $farm): JsonResponse
    {
        if (!$this->ownsFarm($request, $farm)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workLog = $farm->workLogs()->create($request->validated());

        return response()->json(['data' => $workLog], 201);
    }

    public function update(UpdateWorkLogRequest $request, WorkLog $workLog): JsonResponse
    {
        if (!$this->ownsWorkLog($request, $workLog)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workLog->update($request->validated());

        return response()->json(['data' => $workLog->fresh()]);
    }

    public function destroy(Request $request, WorkLog $workLog): JsonResponse
    {
        if (!$this->ownsWorkLog($request, $workLog)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workLog->delete();

        return response()->json(null, 204);
    }

    private function ownsFarm(Request $request, Farm $farm): bool
    {
        $user = $request->attributes->get('auth_user');

        return $user !== null && $farm->app_user_id === $user->id;
    }

    private function ownsWorkLog(Request $request, WorkLog $workLog): bool
    {
        $workLog->loadMissing('farm');

        return $this->ownsFarm($request, $workLog->farm);
    }
}
