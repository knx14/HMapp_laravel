<?php

use App\Models\AppUser;
use App\Models\Farm;
use App\Services\Cognito\CognitoUserResolver;
use App\Services\Cognito\JwtVerifier;
use Illuminate\Support\Facades\DB;

if (!function_exists('phase2ApiAuthHeaders')) {
    function phase2ApiAuthHeaders(AppUser $user): array
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

if (!function_exists('seedPhase2Measurement')) {
    function seedPhase2Measurement(int $farmId, string $date, array $points): void
    {
        foreach ($points as $index => $values) {
            $uploadId = DB::table('uploads')->insertGetId([
                'farm_id' => $farmId,
                'measurement_date' => $date,
                'status' => 'completed',
                'file_path' => "s3://test/farm_{$farmId}/{$date}_{$index}_" . uniqid() . '.csv',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $analysisResultId = DB::table('analysis_results')->insertGetId([
                'upload_id' => $uploadId,
                'latitude' => 35.0 + ($index * 0.001),
                'longitude' => 139.0,
                'sensor_info' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($values as $parameter => $value) {
                DB::table('result_values')->insert([
                    'analysis_result_id' => $analysisResultId,
                    'parameter_name' => $parameter,
                    'parameter_value' => $value,
                    'unit' => $parameter === 'CEC' ? 'me/100g' : 'mg/100g',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

test('timeseries unauthenticated request returns 401', function () {
    $farm = Farm::factory()->create();

    $this->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertStatus(401);
});

test('timeseries returns 403 for another users farm', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);

    $this->withHeaders(phase2ApiAuthHeaders($other))
        ->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertStatus(403);
});

test('timeseries returns empty points when no data exists', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertOk()
        ->assertJson([
            'parameter' => 'CEC',
            'unit' => null,
            'points' => [],
            'work_logs' => [],
        ]);
});

test('timeseries aggregates points by measurement date', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-02-05', [
        ['CEC' => 20.0],
        ['CEC' => 21.0],
        ['CEC' => 22.0],
    ]);
    seedPhase2Measurement($farm->id, '2026-03-10', [
        ['CEC' => 24.0],
        ['CEC' => 25.0],
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertOk()
        ->assertJsonPath('parameter', 'CEC')
        ->assertJsonPath('unit', 'me/100g')
        ->assertJsonPath('points.0.date', '2026-02-05')
        ->assertJsonPath('points.0.avg', 21)
        ->assertJsonPath('points.0.min', 20)
        ->assertJsonPath('points.0.max', 22)
        ->assertJsonPath('points.0.count', 3)
        ->assertJsonPath('points.1.date', '2026-03-10')
        ->assertJsonPath('points.1.avg', 24.5)
        ->assertJsonPath('points.1.count', 2);
});

test('timeseries excludes non completed uploads', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    DB::table('uploads')->insert([
        'farm_id' => $farm->id,
        'measurement_date' => '2026-04-01',
        'status' => 'processing',
        'file_path' => 's3://test/processing.csv',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertOk()
        ->assertJsonPath('points', []);
});

test('timeseries rejects unsupported parameter', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/results/timeseries?parameter=invalid")
        ->assertStatus(422);
});

test('timeseries returns work logs for chart markers', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    DB::table('work_logs')->insert([
        'farm_id' => $farm->id,
        'work_type' => 'fertilization',
        'work_date' => '2026-03-16',
        'title' => 'NK化成 20kg',
        'scope' => 'whole',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/results/timeseries")
        ->assertOk()
        ->assertJsonPath('work_logs.0.date', '2026-03-16')
        ->assertJsonPath('work_logs.0.work_type', 'fertilization')
        ->assertJsonPath('work_logs.0.title', 'NK化成 20kg');
});

test('timeseries accepts each supported parameter', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    foreach (['CEC', 'CaO', 'K2O', 'MgO'] as $parameter) {
        $this->withHeaders(phase2ApiAuthHeaders($user))
            ->getJson("/api/farms/{$farm->id}/results/timeseries?parameter={$parameter}")
            ->assertOk()
            ->assertJsonPath('parameter', $parameter);
    }
});
