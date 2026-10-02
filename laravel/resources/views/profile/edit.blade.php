@extends('layouts.dashboard')

@section('title', '設定')
@section('header-title', '設定')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">ユーザーネーム</h2>
                <form method="POST" action="{{ route('profile.name') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">ユーザーネーム</label>
                        <input id="name" name="name" type="text" required maxlength="255"
                               value="{{ old('name', $user->name) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        保存
                    </button>
                </form>
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">メールアドレス</h2>
                <p class="mt-1 text-sm text-gray-600">現在のメールアドレス: {{ $user->email }}</p>
                <p class="mt-1 text-sm text-gray-600">確認コードを入力するまでは、いまのメールアドレスでログインできます。</p>

                <form method="POST" action="{{ route('profile.email') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">新しいメールアドレス</label>
                        <input id="email" name="email" type="email" required maxlength="255"
                               value="{{ old('email', $pendingEmail) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        確認コードを送る
                    </button>
                </form>

                @if ($pendingEmail)
                    <form method="POST" action="{{ route('profile.email.verify') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">確認コード（{{ $pendingEmail }}）</label>
                            <input id="code" name="code" type="text" required inputmode="numeric" autocomplete="one-time-code"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            メールアドレスを確定する
                        </button>
                    </form>
                    <form method="POST" action="{{ route('profile.email.cancel') }}" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-gray-600 underline">変更を中止する</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">所属</h2>
                <p class="mt-1 text-sm text-gray-600">農業協同組合・支所・会社名などを入力してください。</p>

                <form method="POST" action="{{ route('organization.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_to" value="profile">
                    <div>
                        <label for="organization" class="block text-sm font-medium text-gray-700">所属</label>
                        <input id="organization" name="organization" type="text" required maxlength="{{ \App\Support\OrganizationName::MAX_LENGTH }}"
                               value="{{ old('organization', $user->organization) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('organization')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        保存
                    </button>
                </form>
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">パスワード</h2>
                <form method="POST" action="{{ route('profile.password') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">現在のパスワード</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">新しいパスワード</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">8文字以上で、大文字・小文字・数字・記号をそれぞれ1つ以上含めてください。</p>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">新しいパスワード（確認）</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        保存
                    </button>
                </form>
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">アカウントの削除</h2>
                <p class="mt-1 text-sm text-gray-600">削除すると Web からもモバイルアプリからもログインできなくなります。圃場と測定データは残ります。</p>
                @error('account')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4"
                      onsubmit="return confirm('アカウントを削除します。Web とモバイルの両方でログインできなくなります。よろしいですか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                        アカウントを削除する
                    </button>
                </form>
            </div>
        </div>

        @unless ($user->isAdmin())
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">管理者権限を有効にする</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        管理者から伝えられた管理者キーを入力してください。一度有効にすると、次回からは通常のログインで管理者として使えます。
                    </p>

                    <form method="POST" action="{{ route('profile.admin-key') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="admin_key" class="block text-sm font-medium text-gray-700">管理者キー</label>
                            <input id="admin_key" name="admin_key" type="password" autocomplete="off" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('admin_key')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            有効にする
                        </button>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</div>
@endsection
