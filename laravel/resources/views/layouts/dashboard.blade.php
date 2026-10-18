@php
    $authUser = Auth::user();
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HenryMonitor')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <style>
        [x-cloak] { display: none !important; }
        .app-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 50; height: 4rem; padding-left: 1rem; padding-right: 1rem; }
        .app-header-leading { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
        .app-header-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1.25rem; line-height: 1.75rem; }
        .app-menu-button { display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; }
        .app-menu-button:hover { background: rgba(255, 255, 255, 0.15); }
        .app-shell { padding-top: 4rem; }
        .app-sidebar { position: fixed; left: 0; top: 4rem; z-index: 40; height: calc(100vh - 4rem); transform: translateX(-100%); transition: transform 200ms ease; }
        .app-sidebar.is-open { transform: none; }
        .app-backdrop { position: fixed; top: 4rem; right: 0; bottom: 0; left: 0; z-index: 30; background: rgba(0, 0, 0, 0.4); }
        .app-main { min-width: 0; padding: 1rem; }
        @media (min-width: 640px) {
            .app-header { padding-left: 2rem; padding-right: 2rem; }
            .app-header-title { font-size: 1.5rem; }
            .app-main { padding: 1.5rem; }
        }
        @media (min-width: 1280px) {
            .app-menu-button { display: none; }
            .app-sidebar,
            .app-sidebar.is-open { transform: none; }
            .app-backdrop { display: none !important; }
            .app-main { margin-left: 16rem; }
            body.sidebar-open { overflow: auto; }
        }
        @media (max-width: 1279px) {
            body.sidebar-open { overflow: hidden; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f5f7fa] min-h-screen flex flex-col" x-data="{ sidebarOpen: false }" :class="{ 'sidebar-open': sidebarOpen }" @keydown.escape.window="sidebarOpen = false">

    <header class="app-header fixed top-0 left-0 w-full bg-blue-600 text-white font-bold flex items-center justify-between shadow-lg z-50">
        <div class="app-header-leading">
            <button type="button" class="app-menu-button focus:outline-none" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-controls="app-sidebar" aria-label="メニュー">
                <x-lucide name="menu" class="h-6 w-6" />
            </button>
            <span class="app-header-title">@yield('header-title', 'HenryMonitor')</span>
        </div>
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none" aria-label="アカウントメニュー">
                <x-lucide name="circle-user" class="h-6 w-6 text-white" />
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
                    <div class="font-semibold">{{ $authUser->name }}</div>
                    <div class="text-xs text-gray-500">{{ $authUser->email }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $authUser->isAdmin() ? '管理者' : '一般ユーザー' }}</div>
                </div>
                <hr class="border-gray-200">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">設定</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">ログアウト</button>
                </form>
            </div>
        </div>
    </header>

    <div class="app-shell flex flex-1">
        <div x-show="sidebarOpen" x-cloak class="app-backdrop" @click="sidebarOpen = false"></div>
        <aside id="app-sidebar" class="app-sidebar fixed left-0 w-64 bg-white shadow-lg flex flex-col p-6 overflow-y-auto" :class="{ 'is-open': sidebarOpen }">
            <nav class="flex-1">
                <ul class="space-y-2" @click="sidebarOpen = false">
                    @can('admin')
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="layout-dashboard" class="h-6 w-6" /></span>
                            ダッシュボード
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('user-management.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('user-management.*') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="users" class="h-6 w-6" /></span>
                            ユーザー管理
                        </a>
                    </li>
                    @endcan
                    <li>
                        <a href="{{ route('farm-management.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('farm-management.*') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="land-plot" class="h-6 w-6" /></span>
                            圃場管理
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('measurements.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('measurements.*') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="chart-column" class="h-6 w-6" /></span>
                            測定データ閲覧
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('profile.edit') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="settings" class="h-6 w-6" /></span>
                            設定
                        </a>
                    </li>
                    @can('admin')
                    <li>
                        <a href="{{ route('billing.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('billing.*') ? 'bg-blue-100 text-blue-700 font-bold' : 'text-gray-700 hover:bg-blue-50' }}">
                            <span class="mr-3"><x-lucide name="credit-card" class="h-6 w-6" /></span>
                            課金管理
                        </a>
                    </li>
                    @endcan
                </ul>
            </nav>
        </aside>

        <main class="app-main flex-1 overflow-y-auto">
            @yield('content')
        </main>
    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html> 