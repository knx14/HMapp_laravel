@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- 検索条件フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('data-search.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">ユーザーID</label>
                        <input type="text" name="cognito_id" value="{{ $input['cognito_id'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="ユーザーID">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">場所</label>
                        <input type="text" name="location_info" value="{{ $input['location_info'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="場所">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">シリアルNo</label>
                        <input type="text" name="serial_no" value="{{ $input['serial_no'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="シリアルNo">
                    </div>
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('data-search.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        <!-- 検索結果テーブル -->
        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-xl font-bold mb-4">
                検索結果 @if(!is_null($results)) ({{ $results->count() }}件) @endif
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="px-4 py-2 border-b"><input type="checkbox" /></th>
                            @if(!is_null($results) && $results->count())
                                @foreach(array_keys($results->first()->getAttributes()) as $col)
                                    @if($col === 'id')
                                        @continue
                                    @endif
                                    <th class="px-4 py-2 border-b">
                                        @if($col === 'cognito_id') ユーザーID
                                        @elseif($col === 'location_info') 土地情報
                                        @elseif($col === 'predicted_cec') 推定結果
                                        @else {{ $col }}
                                        @endif
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(!is_null($results) && $results->count())
                            @foreach($results as $row)
                                <tr class="border-b hover:bg-blue-50">
                                    <td class="px-4 py-2"><input type="checkbox" /></td>
                                    @foreach($row->getAttributes() as $col => $val)
                                        @if($col === 'id')
                                            @continue
                                        @endif
                                        <td class="px-4 py-2">{{ $val }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @elseif(!is_null($results))
                            <tr>
                                <td colspan="100" class="text-center py-8 text-gray-400">検索結果がありません</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 