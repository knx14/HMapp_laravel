<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $adminKey = 'testing-admin-key';
    putenv("ADMIN_REGISTRATION_KEY={$adminKey}");
    $_ENV['ADMIN_REGISTRATION_KEY'] = $adminKey;
    $_SERVER['ADMIN_REGISTRATION_KEY'] = $adminKey;

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'admin_key' => $adminKey,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
