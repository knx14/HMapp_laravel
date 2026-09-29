@props(['title'])

<x-guest-layout>
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="bg-white shadow-lg rounded-xl w-full max-w-md p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="mb-2">
                <img src="/images/loginIcon.png" alt="" class="w-10 h-10" />
            </div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $title }}</h2>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
</x-guest-layout>
