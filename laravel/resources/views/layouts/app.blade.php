<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f5f7fa] min-h-screen font-sans antialiased">
    @php $user = Auth::user(); @endphp
    <!-- 画面上部に横長の青いタイトルバーを配置（ダッシュボードのみ表示） -->
    @if(request()->routeIs('dashboard'))
    <div class="w-full bg-blue-600 text-white text-2xl font-bold px-8 py-6 flex items-center justify-between rounded-b-2xl shadow-lg mb-8">
        <span>管理者ダッシュボード</span>
    </div>
    @endif
    <div class="flex min-h-screen pt-0">
        <!-- サイドバー -->
        <aside class="w-80 bg-white shadow-lg flex flex-col p-6 mt-0">
            <nav class="flex-1">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-4 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z" /></svg>
                            </span>
                            ダッシュボード
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-4 rounded-xl transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14a4 4 0 100-8 4 4 0 000 8z" /></svg>
                            </span>
                            ユーザー管理
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('data-search.index') }}" class="flex items-center px-4 py-4 rounded-xl transition {{ request()->routeIs('data-search.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" /></svg>
                            </span>
                            データ検索
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-4 rounded-xl transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="4" y="7" width="16" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a4 4 0 018 0v2" /></svg>
                            </span>
                            課金管理
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-4 rounded-xl transition text-gray-700 hover:bg-blue-50">
                            <span class="mr-3">
                                <img src="{{ asset('images/outupIcon.png') }}" alt="データ出力" class="h-7 w-7">
                            </span>
                            データ出力
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-10 pt-6 border-t text-center">
                <div class="font-semibold text-gray-700 mb-2">{{ $user?->name }}</div>
                <div class="text-xs text-gray-400 mb-4">{{ $user?->email }}</div>
                <a href="{{ route('profile.edit') }}" class="block text-blue-600 hover:underline mb-2">プロフィール</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-red-500 hover:underline">ログアウト</button>
                </form>
            </div>
        </aside>
        <!-- メイン -->
        <main class="flex-1 bg-[#f5f7fa] p-12">
            @yield('content')
        </main>
    </div>
</body>
</html>
