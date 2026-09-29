<?php

use App\Models\AdminRoleEvent;
use App\Models\AppUser;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['admin.grant_key_hash' => Hash::make('correct-admin-key')]);
});

test('settings page shows the admin key form only to general users', function () {
    $user = AppUser::factory()->create();
    $admin = AppUser::factory()->admin()->create();

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('管理者権限を有効にする');

    $this->actingAs($admin)->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('管理者権限を有効にする');
});

test('correct admin key grants admin permanently and records the event', function () {
    $user = AppUser::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.admin-key'), ['admin_key' => 'correct-admin-key'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', '管理者権限を有効にしました。');

    $user->refresh();
    expect($user->isAdmin())->toBeTrue()
        ->and($user->admin_granted_at)->not->toBeNull();

    $event = AdminRoleEvent::sole();
    expect($event->app_user_id)->toBe($user->id)
        ->and($event->action)->toBe(AdminRoleEvent::ACTION_GRANTED)
        ->and($event->actor_app_user_id)->toBe($user->id)
        ->and($event->ip_address)->not->toBeNull();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('wrong admin key is rejected and recorded', function () {
    $user = AppUser::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.admin-key'), ['admin_key' => 'wrong-key'])
        ->assertSessionHasErrors(['admin_key' => '管理者キーが違います。']);

    expect($user->refresh()->isAdmin())->toBeFalse()
        ->and(AdminRoleEvent::where('action', AdminRoleEvent::ACTION_GRANT_FAILED)->count())->toBe(1);
});

test('admin key input is limited to five attempts per user', function () {
    $user = AppUser::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->post(route('profile.admin-key'), ['admin_key' => 'wrong-key']);
    }

    $this->actingAs($user)
        ->post(route('profile.admin-key'), ['admin_key' => 'correct-admin-key'])
        ->assertSessionHasErrors(['admin_key' => 'しばらくしてから再度お試しください。']);

    expect($user->refresh()->isAdmin())->toBeFalse();
});

test('admin key input is limited to ten attempts per ip across users', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->actingAs(AppUser::factory()->create())
            ->post(route('profile.admin-key'), ['admin_key' => 'wrong-key']);
    }

    $user = AppUser::factory()->create();
    $this->actingAs($user)
        ->post(route('profile.admin-key'), ['admin_key' => 'correct-admin-key'])
        ->assertSessionHasErrors(['admin_key' => 'しばらくしてから再度お試しください。']);
});

test('admin key cannot be used when no hash is configured', function () {
    config(['admin.grant_key_hash' => null]);
    $user = AppUser::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.admin-key'), ['admin_key' => 'anything'])
        ->assertSessionHasErrors('admin_key');

    expect($user->refresh()->isAdmin())->toBeFalse();
});

test('role cannot be mass assigned', function () {
    $user = AppUser::create([
        'cognito_sub' => 'sub-mass',
        'name' => 'x',
        'email' => 'x@example.com',
        'role' => AppUser::ROLE_ADMIN,
    ]);

    expect($user->refresh()->role)->toBe(AppUser::ROLE_USER);
});

test('admin can revoke another admin and the change applies immediately', function () {
    $admin = AppUser::factory()->admin()->create();
    $other = AppUser::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('user-management.revoke-admin', $other))
        ->assertRedirect(route('user-management.show', $other));

    $other->refresh();
    expect($other->isAdmin())->toBeFalse()
        ->and($other->admin_granted_at)->toBeNull();

    $event = AdminRoleEvent::where('app_user_id', $other->id)->sole();
    expect($event->action)->toBe(AdminRoleEvent::ACTION_REVOKED)
        ->and($event->actor_app_user_id)->toBe($admin->id);

    $this->actingAs($other)->get(route('dashboard'))->assertForbidden();
});

test('admin cannot revoke themselves', function () {
    $admin = AppUser::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('user-management.revoke-admin', $admin))
        ->assertSessionHasErrors('role');

    expect($admin->refresh()->isAdmin())->toBeTrue();
});

test('general user cannot revoke admins', function () {
    $user = AppUser::factory()->create();
    $admin = AppUser::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('user-management.revoke-admin', $admin))
        ->assertForbidden();

    expect($admin->refresh()->isAdmin())->toBeTrue();
});

test('user management can filter admins and shows role history', function () {
    $admin = AppUser::factory()->admin()->create(['name' => '管理者太郎']);
    AppUser::factory()->create(['name' => '一般花子']);
    AdminRoleEvent::create([
        'app_user_id' => $admin->id,
        'action' => AdminRoleEvent::ACTION_GRANTED,
        'actor_app_user_id' => $admin->id,
        'ip_address' => '10.0.0.1',
    ]);

    $this->actingAs($admin)->get(route('user-management.index', ['role' => 'admin']))
        ->assertOk()
        ->assertSee('管理者太郎')
        ->assertDontSee('一般花子');

    $this->actingAs($admin)->get(route('user-management.show', $admin))
        ->assertOk()
        ->assertSee('管理者権限を付与')
        ->assertSee('10.0.0.1')
        ->assertDontSee('一般ユーザーに戻す');
});

test('admin:list shows admins', function () {
    AppUser::factory()->admin()->create(['email' => 'boss@example.com']);
    AppUser::factory()->create(['email' => 'plain@example.com']);

    $this->artisan('admin:list')
        ->expectsOutputToContain('boss@example.com')
        ->doesntExpectOutputToContain('plain@example.com')
        ->assertSuccessful();
});

test('admin:revoke demotes by email and records a command event', function () {
    $admin = AppUser::factory()->admin()->create(['email' => 'boss@example.com']);

    $this->artisan('admin:revoke', ['email' => 'boss@example.com'])->assertSuccessful();

    expect($admin->refresh()->isAdmin())->toBeFalse();
    $event = AdminRoleEvent::sole();
    expect($event->action)->toBe(AdminRoleEvent::ACTION_REVOKED)
        ->and($event->actor_app_user_id)->toBeNull();
});

test('admin:revoke fails on duplicated email and accepts --sub', function () {
    $a = AppUser::factory()->admin()->create(['email' => 'dup@example.com', 'cognito_sub' => 'sub-a']);
    $b = AppUser::factory()->admin()->create(['email' => 'dup@example.com', 'cognito_sub' => 'sub-b']);

    $this->artisan('admin:revoke', ['email' => 'dup@example.com'])->assertFailed();
    expect($a->refresh()->isAdmin())->toBeTrue();

    $this->artisan('admin:revoke', ['--sub' => 'sub-b'])->assertSuccessful();
    expect($a->refresh()->isAdmin())->toBeTrue()
        ->and($b->refresh()->isAdmin())->toBeFalse();
});

test('admin:hash-key prints a hash that matches the key', function () {
    $this->artisan('admin:hash-key')
        ->expectsQuestion('新しい管理者キー', 'new-admin-key-2026')
        ->expectsQuestion('もう一度入力してください', 'new-admin-key-2026')
        ->expectsOutputToContain("ADMIN_GRANT_KEY_HASH='\$2y\$")
        ->assertSuccessful();
});
