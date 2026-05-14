<?php

use App\Models\AppUser;
use App\Models\Farm;
use App\Models\WorkLog;
use App\Services\Cognito\CognitoUserResolver;
use App\Services\Cognito\JwtVerifier;

function authenticateWorkLogApiAs(AppUser $user): array
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

function validWorkLogPayload(): array
{
    return [
        'work_type' => 'fertilization',
        'work_date' => '2026-03-16',
        'title' => 'NK化成 20kg',
        'amount_value' => 20.0,
        'amount_unit' => 'kg',
    ];
}

test('unauthenticated request returns 401', function () {
    $farm = Farm::factory()->create();

    $this->getJson("/api/v1/farms/{$farm->id}/work-logs")
        ->assertStatus(401);
});

test('another users farm returns 403', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);

    $this->withHeaders(authenticateWorkLogApiAs($other))
        ->getJson("/api/v1/farms/{$farm->id}/work-logs")
        ->assertStatus(403);
});

test('index returns own work logs', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    WorkLog::factory()->count(3)->create(['farm_id' => $farm->id]);

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->getJson("/api/v1/farms/{$farm->id}/work-logs")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('store creates work log', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->postJson("/api/v1/farms/{$farm->id}/work-logs", validWorkLogPayload())
        ->assertCreated()
        ->assertJsonPath('data.work_type', 'fertilization')
        ->assertJsonPath('data.title', 'NK化成 20kg');

    $this->assertDatabaseHas('work_logs', [
        'farm_id' => $farm->id,
        'work_type' => 'fertilization',
        'title' => 'NK化成 20kg',
    ]);
});

test('store fails with invalid work type', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $payload = validWorkLogPayload();
    $payload['work_type'] = 'invalid_type';

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->postJson("/api/v1/farms/{$farm->id}/work-logs", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['work_type']);
});

test('store fails with missing required fields', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->postJson("/api/v1/farms/{$farm->id}/work-logs", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['work_type', 'work_date']);
});

test('update modifies work log', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $workLog = WorkLog::factory()->create([
        'farm_id' => $farm->id,
        'title' => 'Before',
    ]);

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->patchJson("/api/v1/work-logs/{$workLog->id}", ['title' => 'After'])
        ->assertOk()
        ->assertJsonPath('data.title', 'After');
});

test('update by another user returns 403', function () {
    $owner = AppUser::factory()->create();
    $other = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $owner->id]);
    $workLog = WorkLog::factory()->create(['farm_id' => $farm->id]);

    $this->withHeaders(authenticateWorkLogApiAs($other))
        ->patchJson("/api/v1/work-logs/{$workLog->id}", ['title' => 'Hack'])
        ->assertStatus(403);
});

test('destroy deletes work log', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);
    $workLog = WorkLog::factory()->create(['farm_id' => $farm->id]);

    $this->withHeaders(authenticateWorkLogApiAs($user))
        ->deleteJson("/api/v1/work-logs/{$workLog->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('work_logs', ['id' => $workLog->id]);
});
