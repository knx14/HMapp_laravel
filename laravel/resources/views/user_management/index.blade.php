@extends('layouts.dashboard')

@section('title', 'ユーザー管理')
@section('header-title', 'ユーザー管理')

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('error') }}
    </div>
@endif
@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 mx-4">
        {{ $errors->first() }}
    </div>
@endif

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('user-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">ユーザー名</label>
                        <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="ユーザー名">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">所属</label>
                        <input type="text" name="organization" value="{{ $filters['organization'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="所属">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">圃場名</label>
                        <input type="text" name="farm_name" value="{{ $filters['farm_name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="所有する圃場名">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Cognito Sub</label>
                        <input type="text" name="cognito_sub" value="{{ $filters['cognito_sub'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Cognito Sub">
                    </div>
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="role" value="admin" @checked(($filters['role'] ?? '') === 'admin') class="rounded border-gray-300">
                    <span class="font-semibold">管理者のみ</span>
                </label>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">検索</button>
                    <a href="{{ route('user-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">リセット</a>
                </div>
            </form>
        </div>

        <form id="user-export-form" method="POST" action="{{ route('user-management.export') }}">
            @csrf
            @foreach($filters as $name => $value)
                @if($value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
        <form id="user-delete-form" method="POST" action="{{ route('user-management.destroy-selected') }}">
            @csrf
            @foreach($filters as $name => $value)
                @if($value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>

        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">ユーザー一覧</h2>
                    <p class="text-gray-600 mt-1">全{{ $users->total() }}件中 {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }}件を表示。行をクリックすると詳細を表示します。</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" form="user-export-form" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">CSVダウンロード</button>
                    <button type="submit" form="user-delete-form" id="user-delete-button" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">削除</button>
                </div>
            </div>

            @if($users->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input type="checkbox" id="user-select-all" class="rounded" aria-label="このページをすべて選択">
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Cognito Sub</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ユーザー名</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">メールアドレス</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">所属</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">登録日</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">権限</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="user-row cursor-pointer hover:bg-gray-50" data-user-id="{{ $user->id }}">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" form="user-export-form" class="user-row-check rounded" aria-label="選択">
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->cognito_sub ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->email ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->organization ?? '未入力' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $user->created_at?->copy()->timezone(config('measurements.display_timezone'))->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($user->isAdmin())
                                            <span class="inline-block px-2 py-1 rounded bg-purple-100 text-purple-800 font-semibold">管理者</span>
                                        @else
                                            <span class="text-gray-700">一般</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-8 py-6 border-t border-gray-200">
                    {{ $users->appends($filters)->links() }}
                </div>
            @else
                <div class="px-8 py-12 text-center">
                    <div class="text-gray-500 text-lg">検索条件に一致するユーザーが見つかりませんでした。</div>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">ユーザー詳細</h3>
            <button type="button" id="user-modal-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="閉じる">&times;</button>
        </div>
        <div class="p-6">
            <div id="user-modal-loading" class="text-gray-500">読み込み中…</div>
            <div id="user-modal-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
            <div id="user-modal-body" class="hidden space-y-6">
                <dl class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
                    <dt class="font-semibold text-gray-600">Cognito Sub</dt>
                    <dd id="user-detail-sub" class="col-span-2"></dd>
                    <dt class="font-semibold text-gray-600">登録日</dt>
                    <dd id="user-detail-created" class="col-span-2"></dd>
                    <dt class="font-semibold text-gray-600">所属</dt>
                    <dd id="user-detail-organization" class="col-span-2"></dd>
                    <dt class="font-semibold text-gray-600">権限</dt>
                    <dd id="user-detail-role" class="col-span-2"></dd>
                </dl>
                <p class="text-xs text-gray-500">所属は本人が設定します。管理者は変更できません。</p>
                <form id="user-name-form" class="space-y-3 border-t border-gray-200 pt-4">
                    <label class="block text-sm font-semibold">ユーザー名
                        <input type="text" name="name" required maxlength="255" class="mt-1 w-full border rounded px-3 py-2">
                    </label>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">ユーザー名を保存</button>
                </form>
                <form id="user-email-form" class="space-y-3 border-t border-gray-200 pt-4">
                    <label class="block text-sm font-semibold">メールアドレス
                        <input type="email" name="email" required maxlength="255" class="mt-1 w-full border rounded px-3 py-2">
                    </label>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">メールアドレスを保存</button>
                </form>
                <form id="user-revoke-form" method="POST" class="hidden border-t border-gray-200 pt-4">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">一般ユーザーに戻す</button>
                </form>
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="font-semibold mb-3">管理者権限の履歴</h4>
                    <div id="user-detail-events" class="text-sm text-gray-600"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const CSRF = '{{ csrf_token() }}';
    const modal = document.getElementById('user-modal');
    const loading = document.getElementById('user-modal-loading');
    const errorBox = document.getElementById('user-modal-error');
    const body = document.getElementById('user-modal-body');
    const nameForm = document.getElementById('user-name-form');
    const emailForm = document.getElementById('user-email-form');
    const revokeForm = document.getElementById('user-revoke-form');
    let currentId = null;

    document.getElementById('user-select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.user-row-check:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
        syncDeleteChecks();
    });
    document.querySelectorAll('.user-row-check').forEach((checkbox) => {
        checkbox.addEventListener('change', syncDeleteChecks);
    });
    document.getElementById('user-delete-button')?.addEventListener('click', (event) => {
        const count = document.querySelectorAll('.user-row-check:checked').length;
        if (count === 0) {
            event.preventDefault();
            alert('削除するユーザーを選択してください。');
            return;
        }
        if (!confirm(`選択した${count}件のユーザーを削除します。圃場と測定データは残ります。よろしいですか？`)) {
            event.preventDefault();
        }
    });

    function syncDeleteChecks() {
        document.querySelectorAll('#user-delete-form input[name="user_ids[]"]').forEach((node) => node.remove());
        document.querySelectorAll('.user-row-check:checked').forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = checkbox.value;
            document.getElementById('user-delete-form').appendChild(input);
        });
    }

    document.querySelectorAll('.user-row').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('input, a, button')) return;
            openDetail(row.dataset.userId);
        });
    });
    document.getElementById('user-modal-close').addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.classList.add('hidden');
    });

    async function request(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: payload ? JSON.stringify(payload) : null,
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = json.errors ? Object.values(json.errors).flat() : [json.message || '処理に失敗しました。'];
            throw new Error(messages.join('\n'));
        }
        return json;
    }

    async function openDetail(id) {
        currentId = id;
        modal.classList.remove('hidden');
        loading.classList.remove('hidden');
        body.classList.add('hidden');
        errorBox.classList.add('hidden');
        try {
            render((await request(`/users/${id}`)).data);
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }

    function render(data) {
        body.classList.remove('hidden');
        document.getElementById('user-detail-sub').textContent = data.cognito_sub || '-';
        document.getElementById('user-detail-created').textContent = data.created_at || '-';
        document.getElementById('user-detail-organization').textContent = data.organization || '未入力';
        document.getElementById('user-detail-role').textContent = data.role_label;
        nameForm.name.value = data.name || '';
        emailForm.email.value = data.email || '';
        revokeForm.action = `/users/${data.id}/revoke-admin`;
        revokeForm.classList.toggle('hidden', !data.can_revoke);
        const events = document.getElementById('user-detail-events');
        events.replaceChildren();
        if (!data.events || data.events.length === 0) {
            events.textContent = '履歴はありません。';
            return;
        }
        const table = document.createElement('table');
        table.className = 'w-full';
        data.events.forEach((event) => {
            const row = document.createElement('tr');
            [event.at, event.label, event.actor, event.ip || '-'].forEach((text) => {
                const cell = document.createElement('td');
                cell.className = 'py-1 pr-3';
                cell.textContent = text || '';
                row.appendChild(cell);
            });
            table.appendChild(row);
        });
        events.appendChild(table);
    }

    nameForm.addEventListener('submit', (event) => save(event, nameForm, { name: nameForm.name.value }));
    emailForm.addEventListener('submit', (event) => save(event, emailForm, { email: emailForm.email.value }));

    async function save(event, form, payload) {
        event.preventDefault();
        if (!currentId) return;
        errorBox.classList.add('hidden');
        try {
            const json = await request(`/users/${currentId}`, 'PUT', payload);
            render(json.data);
            alert(json.message || '保存しました。一覧の表示は再読み込みで更新されます。');
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        }
    }
})();
</script>
@endsection
