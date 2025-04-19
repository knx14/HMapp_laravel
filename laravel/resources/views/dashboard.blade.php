<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>

                {{-- 今後「過去の解析画像を追加予定のスペース」 --}}
                <div class="mt-6 p-6">
                    <h3 class="text-lg font-bold mb-2">過去の解析結果</h3>
                    <p class="text-sm text-gray-400">ここに過去の結果を画像表示する予定です。</p>
                    {{-- 画像一覧は今後ここに挿入されます --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

