<x-auth-card title="所属の入力">
    <p class="mb-4 text-sm text-gray-600">
        {{ $user->name }} さんの所属（農業協同組合・支所・会社名など）を入力してください。入力が済むと、画面を使えるようになります。
    </p>

    <form method="POST" action="{{ route('organization.update') }}">
        @csrf
        @method('PUT')
        <div>
            <label for="organization" class="block text-sm font-medium text-gray-700">所属</label>
            <input id="organization" name="organization" type="text" required autofocus maxlength="{{ \App\Support\OrganizationName::MAX_LENGTH }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                   value="{{ old('organization') }}">
            @error('organization')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            保存して進む
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center text-sm">
        @csrf
        <button type="submit" class="text-blue-600 hover:underline">ログアウト</button>
    </form>
</x-auth-card>
