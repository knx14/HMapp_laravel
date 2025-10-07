@php
    $user = Auth::user();
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理者ダッシュボード')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f5f7fa] min-h-screen flex flex-col">

    <header class="fixed top-0 left-0 w-full bg-blue-600 text-white text-2xl font-bold px-8 py-6 flex items-center justify-between shadow-lg z-50">
        <span>@yield('header-title', '管理者ダッシュボード')</span>
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg py-1 z-50 origin-top-right"
                 style="display: none;">
                <div class="px-4 py-3 text-sm text-gray-900">
                    <div class="font-semibold">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                </div>
                <hr class="border-gray-200">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">プロフィール</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">ログアウト</button>
                </form>
            </div>
        </div>
    </header>

    <div class="flex flex-1 pt-[72px]">
        <aside class="fixed top-[72px] left-0 h-[calc(100vh-72px)] w-64 bg-white shadow-lg flex flex-col p-6 flex-shrink-0 z-40">
            <nav class="flex-1">
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
                        <a href="{{ route('user-management.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('user-management.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14a4 4 0 100-8 4 4 0 000 8z" /></svg>
                            </span>
                            ユーザー管理
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
                        <a href="{{ route('farm-management.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('farm-management.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                            圃場管理
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('upload-management.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('upload-management.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </span>
                            アップロード管理
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('estimation-results.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('estimation-results.index') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 11h14M7 15h10M9 19h6" />
                                </svg>
                            </span>
                            推定結果閲覧
                        </a>
                    </li>
                    
                </ul>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html> 