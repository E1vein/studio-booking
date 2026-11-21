<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Katalog Studio</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($studios as $studio)
                    <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
                        <x-photo-carousel :photos="$studio->photos" height="h-44" />
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-semibold text-lg">{{ $studio->name }}</h3>
                            <p class="text-sm text-gray-600 mt-1 flex-1">{{ $studio->description }}</p>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span>👥 {{ $studio->capacity }} orang</span>
                                <span class="font-bold text-indigo-600">
                                    Rp {{ number_format($studio->hourly_rate, 0, ',', '.') }}/jam
                                </span>
                            </div>
                            <a href="{{ route('studios.show', $studio) }}"
                               class="mt-4 block text-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                Lihat & Booking
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>