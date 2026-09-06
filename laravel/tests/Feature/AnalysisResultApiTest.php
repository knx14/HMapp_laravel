<?php

use App\Models\AppUser;
use App\Models\Farm;
use App\Services\Cognito\CognitoUserResolver;
use App\Services\Cognito\JwtVerifier;
use Illuminate\Support\Facades\DB;

if (!function_exists('analysisResultApiAuthHeaders')) {
    function analysisResultApiAuthHeaders(AppUser $user): array
    {
        app()->instance(JwtVerifier::class, new class($user) extends JwtVerifier {
            public function __construct(private AppUser $user) {}

            public function verifyToken(string $jwt): array
            {
                return [
                    'claims' => [
                        'sub' => $this->user->cognito_sub,
                        'token_use' => 'access',
                    ],
                ];
            }
        });

        app()->instance(CognitoUserResolver::class, new class($user) extends CognitoUserResolver {
            public function __construct(private AppUser $user) {}

            public function resolve(string $sub, ?string $email = null, ?string $name = null): AppUser
            {
                return $this->user;
            }
        });

        return ['Authorization' => 'Bearer dummy'];
    }
}

if (!function_exists('createAnalysisResultForFarm')) {
    function createAnalysisResultForFarm(int $farmId): int
    {
        $uploadId = DB::table('uploads')->insertGetId([
            'farm_id' => $farmId,
            'file_path' => 's3://test/analysis-result-' . uniqid() . '.csv',
            'measurement_date' => '2026-03-16',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('analysis_results')->insertGetId([
            'upload_id' => $uploadId,
            'sensor_info' => 'test',
            'latitude' => 35.0000000,
            'longitude' => 139.0000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

test('owner_can_update_location', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $analysisResultId = createAnalysisResultForFarm($farm->id);

    $this->withHeaders(analysisResultApiAuthHeaders($user))
        ->patchJson("/api/v1/results/{$analysisResultId}/location", [
            'latitude' => 35.1234567,
            'longitude' => 139.7654321,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'updated')
        ->assertJsonPath('data.id', $analysisResultId);

    $row = DB::table('analysis_results')->where('id', $analysisResultId)->first();
    expect((float) $row->latitude)->toBe(35.1234567)
        ->and((float) $row->longitude)->toBe(139.7654321);
});

test('owner_can_delete_result', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $analysisResultId = createAnalysisResultForFarm($farm->id);
    $uploadId = DB::table('analysis_results')->where('id', $analysisResultId)->value('upload_id');

    DB::table('result_values')->insert([
        'analysis_result_id' => $analysisResultId,
        'parameter_name' => 'CEC',
        'parameter_value' => 21.5,
        'unit' => 'me/100g',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeaders(analysisResultApiAuthHeaders($user))
        ->deleteJson("/api/v1/results/{$analysisResultId}")
        ->assertOk()
        ->assertJson(['message' => 'deleted']);

    $this->assertDatabaseMissing('analysis_results', ['id' => $analysisResultId]);
    $this->assertDatabaseMissing('result_values', ['analysis_result_id' => $analysisResultId]);
    $this->assertDatabaseMissing('uploads', ['id' => $uploadId]);
});

test('other_user_cannot_update_location', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);
    $analysisResultId = createAnalysisResultForFarm($farm->id);

    $this->withHeaders(analysisResultApiAuthHeaders($other))
        ->patchJson("/api/v1/results/{$analysisResultId}/location", [
            'latitude' => 35.1234567,
            'longitude' => 139.7654321,
        ])
        ->assertStatus(403);
});

test('other_user_cannot_delete_result', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);
    $analysisResultId = createAnalysisResultForFarm($farm->id);

    $this->withHeaders(analysisResultApiAuthHeaders($other))
        ->deleteJson("/api/v1/results/{$analysisResultId}")
        ->assertStatus(403);

    $this->assertDatabaseHas('analysis_results', ['id' => $analysisResultId]);
});

test('deleting_one_result_does_not_remove_other_uploads', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $firstId = createAnalysisResultForFarm($farm->id);
    $secondId = createAnalysisResultForFarm($farm->id);
    $firstUploadId = DB::table('analysis_results')->where('id', $firstId)->value('upload_id');
    $secondUploadId = DB::table('analysis_results')->where('id', $secondId)->value('upload_id');

    $this->withHeaders(analysisResultApiAuthHeaders($user))
        ->deleteJson("/api/v1/results/{$firstId}")
        ->assertOk();

    $this->assertDatabaseMissing('uploads', ['id' => $firstUploadId]);
    $this->assertDatabaseMissing('analysis_results', ['id' => $firstId]);
    $this->assertDatabaseHas('uploads', ['id' => $secondUploadId]);
    $this->assertDatabaseHas('analysis_results', ['id' => $secondId]);
});

test('invalid_coordinates_are_rejected', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $analysisResultId = createAnalysisResultForFarm($farm->id);

    $this->withHeaders(analysisResultApiAuthHeaders($user))
        ->patchJson("/api/v1/results/{$analysisResultId}/location", [
            'latitude' => 999,
            'longitude' => 139.7654321,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['latitude']);
});
