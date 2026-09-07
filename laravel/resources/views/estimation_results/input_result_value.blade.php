@extends('layouts.dashboard')

@section('title', '推定結果閲覧 - 測定値入力')
@section('header-title', '推定結果閲覧 - 測定値入力')

@section('content')
@php
    $initialRows = $existingValues->isEmpty()
        ? collect([['name' => 'CEC', 'value' => '']])
        : $existingValues->map(fn ($rv) => [
            'name' => $rv->parameter_name,
            'value' => $rv->parameter_value,
        ])->values();
@endphp
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-2">圃場情報</h2>
            <p class="text-gray-700"><span class="font-semibold">ID:</span> {{ $farm->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">農場名:</span> {{ $farm->farm_name }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">測定点情報</h3>
            <p class="text-gray-700"><span class="font-semibold">緯度:</span> {{ $analysisResult->latitude }}</p>
            <p class="text-gray-700"><span class="font-semibold">経度:</span> {{ $analysisResult->longitude }}</p>
            <p class="text-gray-700"><span class="font-semibold">センサー情報:</span> {{ $analysisResult->sensor_info }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">測定値の入力</h3>
            <p class="text-sm text-gray-500 mb-4">単位はパラメータに応じて自動セットされます（CEC: meq/100g、それ以外: mg/100g）。</p>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('estimation-results.store-result-value', ['farm' => $farm->id, 'analysisResult' => $analysisResult->id]) }}" id="resultValueForm">
                @csrf

                <div id="parameters-container">
                    @foreach($initialRows as $index => $row)
                        <div class="parameter-row mb-4 p-4 border border-gray-300 rounded-md">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        パラメータ名 <span class="text-red-500">*</span>
                                    </label>
                                    <select name="parameters[{{ $index }}][name]"
                                            class="parameter-name w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        @foreach($parameterUnits as $name => $unit)
                                            <option value="{{ $name }}" @selected($row['name'] === $name)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        値 <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="any" name="parameters[{{ $index }}][value]"
                                           value="{{ $row['value'] }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        単位
                                    </label>
                                    <input type="text"
                                           class="parameter-unit w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700"
                                           value="{{ $parameterUnits[$row['name']] ?? '' }}"
                                           readonly
                                           tabindex="-1">
                                </div>
                            </div>
                            <button type="button" class="mt-2 px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 remove-parameter">
                                削除（1つ以上入力してください）
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mb-4">
                    <button type="button" id="add-parameter" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        パラメータを追加
                    </button>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('estimation-results.farm-dates', ['farm' => $farm->id]) }}" 
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        キャンセル
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        送信
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const parameterUnits = @json($parameterUnits);
const parameterNames = Object.keys(parameterUnits);
let parameterIndex = {{ $initialRows->count() }};

function unitFor(name) {
    return parameterUnits[name] || '';
}

function selectedNames() {
    return Array.from(document.querySelectorAll('.parameter-name')).map((el) => el.value);
}

function nextUnusedName() {
    const used = new Set(selectedNames());
    return parameterNames.find((name) => !used.has(name)) || parameterNames[0];
}

function bindRow(row) {
    const nameSelect = row.querySelector('.parameter-name');
    const unitInput = row.querySelector('.parameter-unit');
    nameSelect.addEventListener('change', function () {
        unitInput.value = unitFor(nameSelect.value);
        updateOptionAvailability();
    });
}

function updateOptionAvailability() {
    const used = selectedNames();
    document.querySelectorAll('.parameter-name').forEach((select) => {
        const current = select.value;
        Array.from(select.options).forEach((option) => {
            option.disabled = option.value !== current && used.includes(option.value);
        });
    });
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.parameter-row');
    const removeButtons = document.querySelectorAll('.remove-parameter');
    const addButton = document.getElementById('add-parameter');

    if (rows.length <= 1) {
        removeButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.classList.remove('hover:bg-red-600');
        });
    } else {
        removeButtons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.classList.add('hover:bg-red-600');
        });
    }

    addButton.disabled = rows.length >= parameterNames.length;
    addButton.classList.toggle('opacity-50', addButton.disabled);
    addButton.classList.toggle('cursor-not-allowed', addButton.disabled);
}

function optionHtml(selectedName) {
    return parameterNames.map((name) => {
        const selected = name === selectedName ? ' selected' : '';
        return `<option value="${name}"${selected}>${name}</option>`;
    }).join('');
}

document.querySelectorAll('.parameter-row').forEach(bindRow);

document.getElementById('add-parameter').addEventListener('click', function() {
    if (document.querySelectorAll('.parameter-row').length >= parameterNames.length) {
        return;
    }

    const name = nextUnusedName();
    const container = document.getElementById('parameters-container');
    const newRow = document.createElement('div');
    newRow.className = 'parameter-row mb-4 p-4 border border-gray-300 rounded-md';
    newRow.innerHTML = `
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    パラメータ名 <span class="text-red-500">*</span>
                </label>
                <select name="parameters[${parameterIndex}][name]"
                        class="parameter-name w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    ${optionHtml(name)}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    値 <span class="text-red-500">*</span>
                </label>
                <input type="number" step="any" name="parameters[${parameterIndex}][value]"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    単位
                </label>
                <input type="text"
                       class="parameter-unit w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700"
                       value="${unitFor(name)}"
                       readonly
                       tabindex="-1">
            </div>
        </div>
        <button type="button" class="mt-2 px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 remove-parameter">削除</button>
    `;
    container.appendChild(newRow);
    parameterIndex++;
    bindRow(newRow);
    updateOptionAvailability();
    updateRemoveButtons();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-parameter') && !e.target.disabled) {
        e.target.closest('.parameter-row').remove();
        updateOptionAvailability();
        updateRemoveButtons();
    }
});

updateOptionAvailability();
updateRemoveButtons();
</script>
@endsection
