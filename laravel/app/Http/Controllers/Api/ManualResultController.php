<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreManualResultRequest;
use App\Http\Requests\Api\UpdateManualResultRequest;
use App\Models\AnalysisResult;
use App\Models\AppUser;
use App\Models\Farm;
use App\Models\ResultValue;
use App\Models\Upload;
use App\Services\PolygonCentroidService;
use App\Services\Results\ResultsAggregationService;
use App\Support\SoilParameterUnits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualResultController extends Controller
{
    public function __construct(
        private ResultsAggregationService $results,
        private PolygonCentroidService $centroidService,
    ) {}

    public function store(StoreManualResultRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (!$user instanceof AppUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $farm = Farm::findOrFail($request->integer('farm_id'));

        if ((int) $farm->app_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($farm->isProvisional()) {
            return response()->json(['message' => 'farm_boundary_required'], 422);
        }

        $date = $request->input('measurement_date');
        $duplicateExists = Upload::query()
            ->where('farm_id', $farm->id)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereDate('measurement_date', $date)
            ->whereJsonContains('measurement_parameters->manual_entry', true)
            ->exists();

        if ($duplicateExists) {
            return response()->json(['message' => 'measurement_date_already_exists'], 422);
        }

        $centroid = $this->centroidService->centroidOf(
            $this->results->normalizeBoundaryPolygon($farm->boundary_polygon)
        );

        return DB::transaction(function () use ($request, $farm, $centroid) {
            $upload = Upload::create([
                'farm_id' => $farm->id,
                'measurement_date' => $request->input('measurement_date'),
                'measurement_parameters' => ['manual_entry' => true],
                'file_path' => null,
                'status' => Upload::STATUS_COMPLETED,
            ]);

            $analysisResult = AnalysisResult::create([
                'upload_id' => $upload->id,
                'sensor_info' => json_encode(['manual_entry' => true]),
                'latitude' => $centroid['latitude'],
                'longitude' => $centroid['longitude'],
            ]);

            foreach ($request->input('values', []) as $paramName => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                ResultValue::updateOrCreate(
                    [
                        'analysis_result_id' => $analysisResult->id,
                        'parameter_name' => (string) $paramName,
                    ],
                    [
                        'parameter_value' => $value,
                        'unit' => SoilParameterUnits::unitFor((string) $paramName),
                    ]
                );
            }

            return response()->json(['upload_id' => $upload->id], 201);
        });
    }

    public function update(UpdateManualResultRequest $request, Upload $upload): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (!$user instanceof AppUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $upload->load(['farm', 'analysisResult']);

        if ((int) $upload->farm->app_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (($upload->measurement_parameters['manual_entry'] ?? false) !== true) {
            return response()->json(['message' => 'manual_result_required'], 422);
        }

        if (!$upload->analysisResult) {
            return response()->json(['message' => 'analysis_result_not_found'], 422);
        }

        $date = $request->input('measurement_date');
        $duplicateExists = Upload::query()
            ->where('farm_id', $upload->farm_id)
            ->whereKeyNot($upload->id)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereDate('measurement_date', $date)
            ->whereJsonContains('measurement_parameters->manual_entry', true)
            ->exists();

        if ($duplicateExists) {
            return response()->json(['message' => 'measurement_date_already_exists'], 422);
        }

        return DB::transaction(function () use ($request, $upload) {
            $upload->update([
                'measurement_date' => $request->input('measurement_date'),
            ]);

            ResultValue::query()
                ->where('analysis_result_id', $upload->analysisResult->id)
                ->delete();

            foreach ($request->input('values', []) as $paramName => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                ResultValue::create([
                    'analysis_result_id' => $upload->analysisResult->id,
                    'parameter_name' => (string) $paramName,
                    'parameter_value' => $value,
                    'unit' => SoilParameterUnits::unitFor((string) $paramName),
                ]);
            }

            return response()->json([
                'message' => 'updated',
                'upload_id' => $upload->id,
            ]);
        });
    }

    public function destroy(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        if (!$user instanceof AppUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $upload->load('farm');

        if ((int) $upload->farm->app_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (($upload->measurement_parameters['manual_entry'] ?? false) !== true) {
            return response()->json(['message' => 'manual_result_required'], 422);
        }

        $upload->delete();

        return response()->json(['message' => 'deleted']);
    }
}
