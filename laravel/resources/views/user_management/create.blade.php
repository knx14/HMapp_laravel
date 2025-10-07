@extends('layouts.dashboard')

@section('title', '新規ユーザー登録')
@section('header-title', '新規ユーザー登録')

@section('content')
<div class="py-8">
	<div class="max-w-2xl mx-auto px-4">
		<div class="bg-white rounded-2xl shadow p-8">
			<h2 class="text-xl font-bold mb-6">新規ユーザー登録</h2>
			
			@if ($errors->any())
				<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
					<ul class="list-disc list-inside">
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			@endif

			<form method="POST" action="{{ route('user-management.store') }}" class="space-y-6">
				@csrf
				
				<div>
					<label for="name" class="block font-semibold mb-2">名前 <span class="text-red-500">*</span></label>
					<input 
						type="text" 
						id="name" 
						name="name" 
						value="{{ old('name') }}"
						class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('name') border-red-500 @enderror" 
						placeholder="名前を入力してください"
						required
					>
					@error('name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>

				<div>
					<label for="email" class="block font-semibold mb-2">メールアドレス <span class="text-red-500">*</span></label>
					<input 
						type="email" 
						id="email" 
						name="email" 
						value="{{ old('email') }}"
						class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('email') border-red-500 @enderror" 
						placeholder="メールアドレスを入力してください"
						required
					>
					@error('email')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>

				<div>
					<label for="ja_name" class="block font-semibold mb-2">所属JA名 <span class="text-red-500">*</span></label>
					<input 
						type="text" 
						id="ja_name" 
						name="ja_name" 
						value="{{ old('ja_name') }}"
						class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('ja_name') border-red-500 @enderror" 
						placeholder="所属JA名を入力してください"
						required
					>
					@error('ja_name')
						<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
					@enderror
				</div>

				<div class="flex flex-row gap-4 pt-4">
					<button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition text-lg">
						<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
						</svg>
						登録
					</button>
					<a href="{{ route('user-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-3 px-8 rounded-lg transition text-lg">
						<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
						</svg>
						キャンセル
					</a>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection
