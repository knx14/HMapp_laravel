@php
    $user = Auth::user();
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ダッシュボード</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f5f7fa] min-h-screen">
    <div class="w-full bg-blue-600 text-white text-2xl font-bold px-8 py-6 flex items-center justify-between rounded-b-2xl shadow-lg">
        <span>管理者ダッシュボード</span>
    </div>

    <div class="flex min-h-screen -mt-0"> <aside class="w-64 bg-white shadow-lg flex flex-col p-6 flex-shrink-0"> <nav class="flex-1">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z" /></svg>
                            </span>
                            ダッシュボード
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 rounded-lg transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14a4 4 0 100-8 4 4 0 000 8z" /></svg>
                            </span>
                            ユーザー管理
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('data-search.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('data-search.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" /></svg>
                            </span>
                            データ検索
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 rounded-lg transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="4" y="7" width="16" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a4 4 0 018 0v2" /></svg>
                            </span>
                            課金管理
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 rounded-lg transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <img src="{{ asset('images/outupIcon.png') }}" alt="データ出力" class="h-6 w-6">
                            </span>
                            データ出力
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-10 pt-6 border-t text-center">
                <div class="font-semibold text-gray-700 mb-2">{{ $user->name }}</div>
                <div class="text-xs text-gray-400 mb-4">{{ $user->email }}</div>
                <a href="{{ route('profile.edit') }}" class="block text-blue-600 hover:underline mb-2">プロフィール</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-red-500 hover:underline">ログアウト</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 w-full bg-[#f5f7fa] p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500 flex items-center gap-x-6">
            <div class="p-3 flex-shrink-0">
                <img src="{{ asset('images/usersIcon.png') }}" alt="ユーザーアイコン" class="h-8 w-8 text-blue-500">
            </div>
            <div>
                <div class="text-4xl font-bold text-gray-800 leading-none">
                    {{ number_format($userCount) }}
                </div>
                <p class="text-gray-500 text-lg mt-2">総ユーザー数</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500 flex items-center gap-x-6">
            <div class="p-3 flex-shrink-0">
                <img src="{{ asset('images/uploadIcon.png') }}" alt="アップロードアイコン" class="h-8 w-8 text-green-500">
            </div>
            <div>
                <div class="text-4xl font-bold text-gray-800 leading-none">
                    {{ number_format($uploadCount) }}
                </div>
                <p class="text-gray-500 text-lg mt-2">アップロード数</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500 flex items-center gap-x-6">
            <div class="p-3 flex-shrink-0">
                <img src="{{ asset('images/outupIcon.png') }}" alt="演算処理アイコン" class="h-8 w-8 text-yellow-500">
            </div>
            <div>
                <div class="text-4xl font-bold text-gray-800 leading-none">
                    9,101
                </div>
                <p class="text-gray-500 text-lg mt-2">演算処理数</p>
            </div>
        </div>

    </div>
</main>
    </div>
</body>
</html>