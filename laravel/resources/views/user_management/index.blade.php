@extends('layouts.dashboard')

@section('title', 'ユーザー管理')
@section('header-title', 'ユーザー管理')

@section('content')
<div class="py-8">
	<div class="max-w-6xl mx-auto px-4">
		@if (session('success'))
			<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
				{{ session('success') }}
			</div>
		@endif
		<!-- 検索フォーム -->
		<div class="bg-white rounded-2xl shadow p-8 mb-8">
			<form method="GET" action="{{ route('user-management.index') }}" class="space-y-4">
				<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
					<div>
						<label class="block font-semibold mb-1">名前</label>
						<input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="名前 を入力">
					</div>
					<div>
						<label class="block font-semibold mb-1">登録JA名</label>
						<input type="text" name="ja_name" value="{{ $filters['ja_name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="登録JA名 を入力">
					</div>
				</div>
				<div class="flex flex-row gap-4 mt-6">
					<button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
						<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
						検索
					</button>
					<a href="{{ route('user-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
						<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
						リセット
					</a>
				</div>
			</form>
		</div>

		<!-- 一覧テーブル -->
		<div class="bg-white rounded-2xl shadow p-8">
			<div class="flex justify-between items-center mb-4">
				<h2 class="text-xl font-bold">ユーザー一覧（{{ $users->count() }}件）</h2>
				<a href="{{ route('user-management.create') }}" class="flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
					</svg>
					新規ユーザー登録
				</a>
			</div>
			<div class="overflow-x-auto">
				<table class="min-w-full table-auto border-collapse">
					<thead>
						<tr class="bg-gray-100 text-gray-700">
							<th class="px-4 py-2 border-b">user_id</th>
							<th class="px-4 py-2 border-b">name</th>
							<th class="px-4 py-2 border-b">email</th>
							<th class="px-4 py-2 border-b">ja_name</th>
							<th class="px-4 py-2 border-b">created_at</th>
							<th class="px-4 py-2 border-b">updated_at</th>
						</tr>
					</thead>
					<tbody>
						@forelse($users as $user)
							<tr class="border-b hover:bg-blue-50">
								<td class="px-4 py-2">{{ $user->id }}</td>
								<td class="px-4 py-2">{{ $user->name }}</td>
								<td class="px-4 py-2">{{ $user->email }}</td>
								<td class="px-4 py-2">{{ $user->ja_name }}</td>
								<td class="px-4 py-2">{{ $user->created_at }}</td>
								<td class="px-4 py-2">{{ $user->updated_at }}</td>
							</tr>
						@empty
							<tr>
								<td colspan="6" class="text-center py-8 text-gray-400">データがありません</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
@endsection


