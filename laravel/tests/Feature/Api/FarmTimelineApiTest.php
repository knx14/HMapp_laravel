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

test('timeline unauthenticated request returns 401', function () {
    $farm = Farm::factory()->create();

    $this->getJson("/api/farms/{$farm->id}/timeline")
        ->assertStatus(401);
});

test('timeline returns 403 for another users farm', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);

    $this->withHeaders(phase2ApiAuthHeaders($other))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertStatus(403);
});

test('timeline returns empty items when no data exists', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJson(['items' => []]);
});

test('timeline items are sorted newest first', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-02-05', [['CEC' => 21.0]]);
    seedPhase2Measurement($farm->id, '2026-03-28', [['CEC' => 24.3]]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJsonPath('items.0.date', '2026-03-28')
        ->assertJsonPath('items.1.date', '2026-02-05');
});

test('timeline calculates cec delta from previous measurement', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-02-05', [['CEC' => 21.0]]);
    seedPhase2Measurement($farm->id, '2026-03-10', [['CEC' => 24.3]]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJsonPath('items.0.delta.CEC', 3.3)
        ->assertJsonPath('items.1.delta.CEC', null);
});

test('timeline includes work logs with measurements', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-03-28', [['CEC' => 24.3]]);
    DB::table('work_logs')->insert([
        'farm_id' => $farm->id,
        'work_type' => 'fertilization',
        'work_date' => '2026-03-16',
        'title' => 'NK化成 20kg',
        'detail' => '追肥',
        'amount_value' => 20,
        'amount_unit' => 'kg',
        'scope' => 'whole',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJsonPath('items.0.type', 'measurement')
        ->assertJsonPath('items.1.type', 'work_log')
        ->assertJsonPath('items.1.work_type', 'fertilization')
        ->assertJsonPath('items.1.amount_value', 20);
});

test('timeline measurement values contain supported parameter stats', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-03-28', [
        ['CEC' => 24.0, 'CaO' => 140.0, 'K2O' => 88.0, 'MgO' => 37.0],
        ['CEC' => 26.0, 'CaO' => 144.0, 'K2O' => 90.0, 'MgO' => 39.0],
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJsonPath('items.0.values.CEC.avg', 25)
        ->assertJsonPath('items.0.values.CEC.min', 24)
        ->assertJsonPath('items.0.values.CEC.max', 26)
        ->assertJsonPath('items.0.values.CaO.avg', 142)
        ->assertJsonPath('items.0.values.K2O.avg', 89)
        ->assertJsonPath('items.0.values.MgO.avg', 38)
        ->assertJsonPath('items.0.delta.CEC', null);
});

test('timeline sorts same day measurement before work log', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    seedPhase2Measurement($farm->id, '2026-03-28', [['CEC' => 24.3]]);
    DB::table('work_logs')->insert([
        'farm_id' => $farm->id,
        'work_type' => 'tillage',
        'work_date' => '2026-03-28',
        'title' => '耕うん',
        'scope' => 'whole',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withHeaders(phase2ApiAuthHeaders($user))
        ->getJson("/api/farms/{$farm->id}/timeline")
        ->assertOk()
        ->assertJsonPath('items.0.type', 'measurement')
        ->assertJsonPath('items.1.type', 'work_log');
});
