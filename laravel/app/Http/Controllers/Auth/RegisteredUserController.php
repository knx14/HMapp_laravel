<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.auth');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 管理者キーの検証
        $adminKey = env('ADMIN_REGISTRATION_KEY');
        if (empty($adminKey)) {
            return back()->withErrors([
                'admin_key' => '管理者キーが設定されていません。システム管理者にお問い合わせください。',
            ])->withInput($request->only('name', 'email'));
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'admin_key' => ['required', 'string'],
        ]);

        // 管理者キーの一致確認
        if ($request->admin_key !== $adminKey) {
            return back()->withErrors([
                'admin_key' => '管理者キーが正しくありません。',
            ])->withInput($request->only('name', 'email'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
