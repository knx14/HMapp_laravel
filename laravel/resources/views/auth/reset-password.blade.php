<x-auth-card title="新しいパスワードの設定">
    <p class="mb-4 text-sm text-gray-600">メールで届いた確認コードと、新しいパスワードを入力してください。</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
            <input id="email" name="email" type="email" required autocomplete="username"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                   value="{{ old('email', $email) }}">
            @error('email')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mt-4">
            <label for="code" class="block text-sm font-medium text-gray-700">確認コード</label>
            <input id="code" name="code" type="text" inputmode="numeric" required autocomplete="one-time-code"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                   value="{{ old('code') }}">
            @error('code')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        @include('auth.partials.new-password-fields')
        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            パスワードを再設定
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">確認コードを再送する</a>
    </div>
</x-auth-card>
