<x-app-layout>
    <x-slot name="header">
        <h2>CSVアップロード</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8">
        @if(session('success'))
            <div class="mb-4 text-green-600 font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('csv.upload.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="csv_file" required>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">アップロード</button>
        </form>
    </div>
</x-app-layout>
