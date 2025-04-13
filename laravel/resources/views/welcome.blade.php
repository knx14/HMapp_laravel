{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ダッシュボード</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="flex">
        <!-- サイドバー -->
        <aside class="w-64 bg-white p-6 shadow">
            <h2 class="text-xl font-bold mb-4">メニュー</h2>
            <ul class="space-y-2">
                @guest
                    <li><a href="{{ route('login') }}" class="text-blue-600 hover:underline">ログイン</a></li>
                    <li><a href="{{ route('register') }}" class="text-blue-600 hover:underline">新規登録</a></li>
                @else
                    <li><a href="{{ route('dashboard') }}" class="text-green-600 hover:underline">ダッシュボード</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-red-500 hover:underline">ログアウト</button>
                        </form>
                    </li>
                @endguest
            </ul>
        </aside>

        <!-- メインコンテンツ -->
        <main class="flex-1 p-10">
            <h1 class="text-2xl font-semibold mb-4">ホームページ</h1>
            <p>ここにページ内容を書く</p>
        </main>
    </div>
</body>
</html>
