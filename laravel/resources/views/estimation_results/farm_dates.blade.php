@extends('layouts.dashboard')

@section('title', '推定結果閲覧 - 測定日選択')
@section('header-title', '推定結果閲覧')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-2">圃場情報</h2>
            <p class="text-gray-700"><span class="font-semibold">ID:</span> {{ $farm->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">農場名:</span> {{ $farm->farm_name }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">推定された日付を選択</h3>
            @if(empty($groupedDates) || $groupedDates->isEmpty())
                <div class="text-gray-500">この圃場には完了した推定結果（アップロード）がありません。</div>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach($groupedDates as $row)
                        <li class="py-3 flex items-center justify-between">
                            <div class="text-gray-800">{{ $row->measurement_date }}</div>
                            <a href="{{ route('estimation-results.cec', ['farm' => $farm->id, 'upload' => $row->upload_id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold">この日付の結果を見る</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">結果入力</h3>
            
            @if((empty($pendingUploads) || $pendingUploads->isEmpty()) && (empty($processingUploads) || $processingUploads->isEmpty()))
                <div class="text-gray-500 mb-4">入力待ちのアップロードがありません。</div>
            @else
                @if(!empty($pendingUploads) && $pendingUploads->isNotEmpty())
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-700 mb-3">測定点入力待ち：</p>
                        <ul class="divide-y divide-gray-200">
                            @foreach($pendingUploads as $upload)
                                <li class="py-3 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-800 font-medium">ID: {{ $upload->id }}</span>
                                        @if($upload->measurement_date)
                                            <span class="text-gray-600 ml-2">（{{ $upload->measurement_date->format('Y-m-d') }}）</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('estimation-results.input', ['farm' => $farm->id]) }}?upload_id={{ $upload->id }}" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold">測定点を入力</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($processingUploads) && $processingUploads->isNotEmpty())
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-3">測定値入力待ち（測定点入力済み）：</p>
                        <ul class="divide-y divide-gray-200">
                            @foreach($processingUploads as $upload)
                                <li class="py-3 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-800 font-medium">ID: {{ $upload->id }}</span>
                                        @if($upload->measurement_date)
                                            <span class="text-gray-600 ml-2">（{{ $upload->measurement_date->format('Y-m-d') }}）</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('estimation-results.input-result-value', ['farm' => $farm->id, 'analysisResult' => $upload->analysisResult->id]) }}" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold">測定値を入力</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection


