<x-auth-card title="新しいパスワードの設定">
    <p class="mb-4 text-sm text-gray-600">初回ログインのため、新しいパスワードを設定してください。</p>

    <form method="POST" action="{{ route('password.challenge.store') }}">
        @csrf
        @include('auth.partials.new-password-fields')
        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            設定してログイン
        </button>
    </form>
</x-auth-card>
