<?php

namespace App\Http\Requests\Auth;

use App\Services\Cognito\CognitoAuthException;
use App\Services\Cognito\CognitoAuthResult;
use App\Services\Cognito\CognitoAuthService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }

    /**
     * Cognito で認証する。パスワード誤りはレート制限の対象として数える。
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws CognitoAuthException パスワードの再設定が必要なときなど、画面遷移で扱うもの
     */
    public function authenticate(CognitoAuthService $cognito): CognitoAuthResult
    {
        $this->ensureIsNotRateLimited();

        try {
            $result = $cognito->authenticate($this->email(), (string) $this->input('password'));
        } catch (CognitoAuthException $e) {
            if ($e->isInvalidCredentials()) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'メールアドレスまたはパスワードが違います。',
                ]);
            }

            throw $e;
        }

        RateLimiter::clear($this->throttleKey());

        return $result;
    }

    public function email(): string
    {
        return Str::lower(trim((string) $this->input('email')));
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'ログインの試行回数が多すぎます。'.(int) ceil($seconds / 60).'分後に再度お試しください。',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate($this->email().'|'.$this->ip());
    }
}
