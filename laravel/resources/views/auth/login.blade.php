<x-auth-card title="ログイン">
    <p class="mb-4 text-sm text-gray-600">モバイルアプリと同じメールアドレス・パスワードでログインしてください。</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                   value="{{ old('email') }}">
            @error('email')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mt-4">
            <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            @error('password')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ログイン
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">パスワードを忘れた方はこちら</a>
    </div>
    <p class="mt-4 text-center text-xs text-gray-500">アカウントをお持ちでない方は、モバイルアプリから新規登録してください。</p>
</x-auth-card>
