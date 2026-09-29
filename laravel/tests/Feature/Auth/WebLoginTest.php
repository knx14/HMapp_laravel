<?php

use App\Models\AppUser;
use App\Services\Cognito\CognitoAuthException;
use App\Services\Cognito\CognitoAuthResult;
use App\Services\Cognito\CognitoAuthService;
use Illuminate\Support\Facades\Crypt;

test('login screen can be rendered', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('モバイルアプリと同じメールアドレス・パスワード');
});

test('general user logs in with cognito and lands on farm management', function () {
    $user = AppUser::factory()->create(['email' => 'user@example.com']);
    fakeWebIdTokenVerifier($user->cognito_sub);

    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->once()
        ->with('user@example.com', 'Secret-pass1')
        ->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens()));

    $response = $this->post('/login', ['email' => 'User@Example.com', 'password' => 'Secret-pass1']);

    $response->assertRedirect(route('farm-management.index', absolute: false));
    $this->assertAuthenticatedAs($user);

    $session = session('cognito');
    expect($session['username'])->toBe($user->cognito_sub)
        ->and(Crypt::decryptString($session['refresh_token']))->toBe('refresh-token');
});

test('admin lands on dashboard after login', function () {
    $admin = AppUser::factory()->admin()->create();
    fakeWebIdTokenVerifier($admin->cognito_sub);

    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens()));

    $this->post('/login', ['email' => $admin->email, 'password' => 'Secret-pass1'])
        ->assertRedirect(route('dashboard', absolute: false));
});

test('wrong password shows an error and stays logged out', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->andThrow(new CognitoAuthException(CognitoAuthException::NOT_AUTHORIZED));

    $this->from('/login')
        ->post('/login', ['email' => 'user@example.com', 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが違います。']);

    $this->assertGuest();
});

test('login is throttled after five failures', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->times(5)
        ->andThrow(new CognitoAuthException(CognitoAuthException::NOT_AUTHORIZED));

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('試行回数が多すぎます');
});

test('unconfirmed user is told to finish confirmation in the mobile app', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->andThrow(new CognitoAuthException(CognitoAuthException::USER_NOT_CONFIRMED));

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'Secret-pass1'])
        ->assertSessionHasErrors(['email' => 'モバイルアプリで確認コードの入力を完了してください。']);
});

test('cognito user without app_users row cannot log in', function () {
    fakeWebIdTokenVerifier('missing-sub');

    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens()));

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'Secret-pass1'])
        ->assertSessionHasErrors(['email' => 'アカウント情報が見つかりません。管理者にお問い合わせください。']);

    $this->assertGuest();
});

test('password reset required redirects to forgot password', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('authenticate')
        ->andThrow(new CognitoAuthException(CognitoAuthException::PASSWORD_RESET_REQUIRED));

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'Secret-pass1'])
        ->assertRedirect(route('password.request'));

    $this->assertGuest();
});

test('new password challenge completes login', function () {
    $user = AppUser::factory()->create();
    fakeWebIdTokenVerifier($user->cognito_sub);

    $mock = $this->mock(CognitoAuthService::class);
    $mock->shouldReceive('authenticate')
        ->andReturn(CognitoAuthResult::challenge(CognitoAuthResult::CHALLENGE_NEW_PASSWORD_REQUIRED, 'challenge-session', 'cognito-username'));
    $mock->shouldReceive('respondToNewPasswordChallenge')
        ->once()
        ->with('cognito-username', 'challenge-session', 'New-pass123!')
        ->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens()));

    $this->post('/login', ['email' => $user->email, 'password' => 'Temp-pass1'])
        ->assertRedirect(route('password.challenge'));
    $this->assertGuest();

    $this->get(route('password.challenge'))->assertOk();

    $this->post(route('password.challenge.store'), [
        'password' => 'New-pass123!',
        'password_confirmation' => 'New-pass123!',
    ])->assertRedirect(route('farm-management.index', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('new password screen without a challenge goes back to login', function () {
    $this->get(route('password.challenge'))->assertRedirect(route('login'));
});

test('logout revokes the web refresh token', function () {
    $user = AppUser::factory()->create();
    fakeWebIdTokenVerifier($user->cognito_sub);

    $mock = $this->mock(CognitoAuthService::class);
    $mock->shouldReceive('authenticate')->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens('rt-123')));
    $mock->shouldReceive('revoke')->once()->with('rt-123');

    $this->post('/login', ['email' => $user->email, 'password' => 'Secret-pass1']);
    $this->assertAuthenticated();

    $this->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('expired access token is refreshed', function () {
    $user = AppUser::factory()->create();
    fakeWebIdTokenVerifier($user->cognito_sub);

    $mock = $this->mock(CognitoAuthService::class);
    $mock->shouldReceive('authenticate')->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens('rt-1', 0)));
    $mock->shouldReceive('refresh')->once()->with($user->cognito_sub, 'rt-1')->andReturn(fakeCognitoTokens('rt-1', 3600));

    $this->post('/login', ['email' => $user->email, 'password' => 'Secret-pass1']);

    $this->get(route('farm-management.index'))->assertOk();
    expect(session('cognito.expires_at'))->toBeGreaterThan(now()->getTimestamp() + 3000);
});

test('failed refresh logs the user out', function () {
    $user = AppUser::factory()->create();
    fakeWebIdTokenVerifier($user->cognito_sub);

    $mock = $this->mock(CognitoAuthService::class);
    $mock->shouldReceive('authenticate')->andReturn(CognitoAuthResult::authenticated(fakeCognitoTokens('rt-1', 0)));
    $mock->shouldReceive('refresh')->andThrow(new CognitoAuthException(CognitoAuthException::NOT_AUTHORIZED));
    $mock->shouldReceive('revoke');

    $this->post('/login', ['email' => $user->email, 'password' => 'Secret-pass1']);

    $this->get(route('farm-management.index'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('registration routes no longer exist', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

test('forgot password sends a code and reset confirms it', function () {
    $mock = $this->mock(CognitoAuthService::class);
    $mock->shouldReceive('forgotPassword')->once()->with('user@example.com');
    $mock->shouldReceive('confirmForgotPassword')->once()->with('user@example.com', '123456', 'New-pass123!');

    $this->post(route('password.email'), ['email' => 'User@example.com'])
        ->assertRedirect(route('password.reset', ['email' => 'user@example.com']));

    $this->get(route('password.reset', ['email' => 'user@example.com']))
        ->assertOk()
        ->assertSee('user@example.com');

    $this->post(route('password.store'), [
        'email' => 'user@example.com',
        'code' => '123456',
        'password' => 'New-pass123!',
        'password_confirmation' => 'New-pass123!',
    ])->assertRedirect(route('login'));
});

test('forgot password does not reveal unknown accounts', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('forgotPassword')
        ->andThrow(new CognitoAuthException(CognitoAuthException::USER_NOT_FOUND));

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect(route('password.reset', ['email' => 'nobody@example.com']))
        ->assertSessionHasNoErrors();
});

test('wrong confirmation code shows an error', function () {
    $this->mock(CognitoAuthService::class)
        ->shouldReceive('confirmForgotPassword')
        ->andThrow(new CognitoAuthException(CognitoAuthException::CODE_MISMATCH));

    $this->post(route('password.store'), [
        'email' => 'user@example.com',
        'code' => '000000',
        'password' => 'New-pass123!',
        'password_confirmation' => 'New-pass123!',
    ])->assertSessionHasErrors(['code' => '確認コードが違います。']);
});
