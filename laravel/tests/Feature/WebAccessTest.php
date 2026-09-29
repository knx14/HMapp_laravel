<?php

use App\Models\AppUser;
use App\Models\Farm;
use App\Services\Cognito\CognitoUserResolver;
use App\Services\Cognito\JwtVerifier;

if (! function_exists('webAccessApiAuthHeaders')) {
    function webAccessApiAuthHeaders(AppUser $user): array
    {
        app()->instance(JwtVerifier::class, new class($user) extends JwtVerifier
        {
            public function __construct(private AppUser $user) {}

            public function verifyToken(string $jwt): array
            {
                return ['claims' => ['sub' => $this->user->cognito_sub, 'token_use' => 'access']];
            }
        });

        app()->instance(CognitoUserResolver::class, new class($user) extends CognitoUserResolver
        {
            public function __construct(private AppUser $user) {}

            public function resolve(string $sub, ?string $email = null, ?string $name = null): AppUser
            {
                return $this->user;
            }
        });

        return ['Authorization' => 'Bearer dummy'];
    }
}

function boundaryPolygon(): array
{
    return ['boundary_polygon' => [[35.0, 139.0], [35.0, 139.1], [35.1, 139.1], [35.1, 139.0]]];
}

test('guests are sent to login', function () {
    $this->get(route('farm-management.index'))->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->getJson('/api/farms/1/boundary')->assertUnauthorized();
});

test('logged in user visiting top page goes to their home', function () {
    $this->actingAs(AppUser::factory()->create())
        ->get('/')->assertRedirect(route('farm-management.index', absolute: false));

    $this->actingAs(AppUser::factory()->admin()->create())
        ->get('/')->assertRedirect(route('dashboard', absolute: false));
});

test('general user cannot open admin only screens', function (string $uri) {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(str_replace('{farm}', (string) $farm->id, $uri))
        ->assertForbidden();
})->with([
    '/dashboard',
    '/users',
    '/farms/create',
    '/uploads',
    '/uploads/create',
    '/estimation-results/farms/{farm}/input',
]);

test('general user cannot post to admin only endpoints', function () {
    $user = AppUser::factory()->create();
    $farm = Farm::factory()->create(['app_user_id' => $user->id]);

    $this->actingAs($user)->post(route('farm-management.store'), [])->assertForbidden();
    $this->actingAs($user)->post(route('upload-management.store'), [])->assertForbidden();
    $this->actingAs($user)->post(route('estimation-results.store-analysis-result', ['farm' => $farm->id]), [])->assertForbidden();
});

test('general user sees only their visible farms', function () {
    $user = AppUser::factory()->create();
    Farm::factory()->create(['app_user_id' => $user->id, 'farm_name' => '自分の圃場']);
    Farm::factory()->create(['app_user_id' => $user->id, 'farm_name' => '非表示の自分の圃場', 'hidden_at' => now()]);
    Farm::factory()->create(['farm_name' => '他人の圃場']);

    foreach (['farm-management.index', 'estimation-results.index'] as $route) {
        $this->actingAs($user)->get(route($route))
            ->assertOk()
            ->assertSee('自分の圃場')
            ->assertDontSee('非表示の自分の圃場')
            ->assertDontSee('他人の圃場');
    }
});

test('general user menu hides admin items', function () {
    $this->actingAs(AppUser::factory()->create())
        ->get(route('farm-management.index'))
        ->assertOk()
        ->assertDontSee('ダッシュボード')
        ->assertDontSee('ユーザー管理')
        ->assertDontSee('アップロード管理')
        ->assertDontSee('圃場を追加')
        ->assertSee('推定結果閲覧')
        ->assertSee('設定');
});

test('admin sees all farms including hidden ones', function () {
    $admin = AppUser::factory()->admin()->create();
    Farm::factory()->create(['farm_name' => '他人の圃場']);
    Farm::factory()->create(['farm_name' => '非表示の圃場', 'hidden_at' => now()]);

    $this->actingAs($admin)->get(route('farm-management.index'))
        ->assertOk()
        ->assertSee('他人の圃場')
        ->assertSee('非表示の圃場')
        ->assertSee('ユーザー管理');
});

test('estimation result pages follow farm permissions', function () {
    $user = AppUser::factory()->create();
    $own = Farm::factory()->create(['app_user_id' => $user->id]);
    $others = Farm::factory()->create();
    $hidden = Farm::factory()->create(['app_user_id' => $user->id, 'hidden_at' => now()]);

    $this->actingAs($user)->get(route('estimation-results.farm-dates', ['farm' => $own->id]))
        ->assertOk()
        ->assertDontSee('結果入力');
    $this->actingAs($user)->get(route('estimation-results.farm-dates', ['farm' => $others->id]))->assertForbidden();
    $this->actingAs($user)->get(route('estimation-results.farm-dates', ['farm' => $hidden->id]))->assertForbidden();
    $this->actingAs($user)->get(route('estimation-results.cec', ['farm' => $others->id, 'upload' => 1]))->assertForbidden();

    $admin = AppUser::factory()->admin()->create();
    $this->actingAs($admin)->get(route('estimation-results.farm-dates', ['farm' => $others->id]))
        ->assertOk()
        ->assertSee('結果入力');
});

test('farm boundary api requires permission', function () {
    $user = AppUser::factory()->create();
    $own = Farm::factory()->create(['app_user_id' => $user->id, 'boundary_polygon' => boundaryPolygon()]);
    $others = Farm::factory()->create(['boundary_polygon' => boundaryPolygon()]);

    $this->actingAs($user)->getJson("/api/farms/{$own->id}/boundary")
        ->assertOk()
        ->assertJsonPath('data.farm_id', $own->id);
    $this->actingAs($user)->getJson("/api/farms/{$others->id}/boundary")->assertForbidden();

    $this->actingAs(AppUser::factory()->admin()->create())
        ->getJson("/api/farms/{$others->id}/boundary")
        ->assertOk();
});

test('removed unauthenticated apis are gone', function () {
    $farm = Farm::factory()->create();

    $this->getJson('/api/analysis/summary')->assertNotFound();
    $this->getJson('/api/uploads/1/analysis-data')->assertNotFound();
    $this->getJson("/api/farms/{$farm->id}/measurements")->assertNotFound();
});

test('mobile api scope is the same for admins', function () {
    $admin = AppUser::factory()->admin()->create();
    $own = Farm::factory()->create(['app_user_id' => $admin->id, 'farm_name' => '管理者の圃場']);
    $others = Farm::factory()->create(['farm_name' => '他人の圃場', 'boundary_polygon' => boundaryPolygon()]);
    $headers = webAccessApiAuthHeaders($admin);

    $this->getJson('/api/v1/farms', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id);

    $this->getJson('/api/farms/with-latest-result', $headers)
        ->assertOk()
        ->assertJsonMissing(['farm_name' => '他人の圃場']);

    $this->putJson("/api/v1/farms/{$others->id}", ['farm_name' => '書き換え'], $headers)->assertForbidden();
    $this->deleteJson("/api/v1/farms/{$others->id}", [], $headers)->assertForbidden();
    $this->getJson("/api/farms/{$others->id}/timeline", $headers)->assertForbidden();
});
