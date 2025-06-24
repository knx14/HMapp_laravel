<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RDSデータ検索') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- エラーメッセージ表示 -->
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- 検索フォーム -->
                    <form method="GET" action="{{ route('rds.search') }}" class="mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            
                            <!-- ユーザーID検索 -->
                            <div>
                                <label for="cognito_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    ユーザーID (Cognito ID)
                                </label>
                                <input type="text" 
                                       id="cognito_id" 
                                       name="cognito_id" 
                                       value="{{ request('cognito_id') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="ユーザーIDを入力">
                            </div>

                            <!-- 特定日付検索 -->
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                                    日付
                                </label>
                                <input type="date" 
                                       id="date" 
                                       name="date" 
                                       value="{{ request('date') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- 開始日検索 -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    開始日
                                </label>
                                <input type="date" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="{{ request('start_date') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- 終了日検索 -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    終了日
                                </label>
                                <input type="date" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="{{ request('end_date') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- 検索ボタン -->
                        <div class="mt-4 flex gap-2">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                検索
                            </button>
                            <a href="{{ route('rds.search') }}" 
                               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                クリア
                            </a>
                        </div>
                    </form>

                    <!-- 検索結果 -->
                    @if(isset($results))
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                検索結果 ({{ $results->total() }}件)
                            </h3>

                            @if($results->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white border border-gray-300">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="px-6 py-3 border-b border-gray-300 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    ID
                                                </th>
                                                <th class="px-6 py-3 border-b border-gray-300 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Cognito ID
                                                </th>
                                                <th class="px-6 py-3 border-b border-gray-300 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    作成日時
                                                </th>
                                                <!-- 他のカラムがあれば追加 -->
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($results as $result)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $result->id }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $result->cognito_id }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $result->created_at ? $result->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                                    </td>
                                                    <!-- 他のカラムがあれば追加 -->
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ページネーション -->
                                <div class="mt-4">
                                    {{ $results->appends(request()->query())->links() }}
                                </div>

                            @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500">検索条件に一致するデータが見つかりませんでした。</p>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout> 