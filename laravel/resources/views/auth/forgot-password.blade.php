<x-auth-card title="パスワードの再設定">
    <p class="mb-4 text-sm text-gray-600">登録しているメールアドレスを入力してください。確認コードをメールで送ります。</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                   value="{{ old('email') }}">
            @error('email')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            確認コードを送信
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">ログイン画面に戻る</a>
    </div>
</x-auth-card>
