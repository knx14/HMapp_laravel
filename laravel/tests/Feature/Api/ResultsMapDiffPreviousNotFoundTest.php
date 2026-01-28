<?php

use App\Models\AppUser;
use App\Models\Farm;
use App\Models\Upload;
use App\Services\Cognito\CognitoUserResolver;
use App\Services\Cognito\JwtVerifier;

test('map-diff returns 404 previous_not_found when no previous date exists', function () {
    $appUser = AppUser::create([
        'cognito_sub' => 'sub-test',
        'name' => 'Test',
        'email' => 'test@example.com',
        'ja_name' => 'テスト',
    ]);

    $farm = Farm::create([
        'app_user_id' => $appUser->id,
        'farm_name' => '圃場A',
        'cultivation_method' => null,
        'crop_type' => null,
        'boundary_polygon' => null,
    ]);

    Upload::create([
        'farm_id' => $farm->id,
        'file_path' => 's3://dummy/current.csv',
        'measurement_date' => '2026-01-27',
        'measurement_parameters' => null,
        'note1' => null,
        'note2' => null,
        'cultivation_type' => null,
        'status' => Upload::STATUS_COMPLETED,
    ]);

    app()->instance(JwtVerifier::class, new class {
        public function verifyToken(string $jwt): array
        {
            return [
                'claims' => [
                    'sub' => 'sub-test',
                    'token_use' => 'access',
                ],
            ];
        }
    });

    app()->instance(CognitoUserResolver::class, new class($appUser) {
        public function __construct(private AppUser $user) {}

        public function resolve(string $sub, ?string $email = null, ?string $name = null): AppUser
        {
            return $this->user;
        }
    });

    $res = $this
        ->withHeader('Authorization', 'Bearer dummy')
        ->getJson("/api/farms/{$farm->id}/results/map-diff?date=2026-01-27");

    $res->assertStatus(404);
    $res->assertExactJson(['message' => 'previous_not_found']);
});

