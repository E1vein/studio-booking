<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Studio</h2>
            <a href="{{ route('admin.studios.create') }}"
               class="bg-indigo-600 text-white text-sm px-4 py-2 rounded hover:bg-indigo-700">+ Tambah Studio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="p-3 pl-6">Studio</th>
                            <th class="p-3">Tarif/jam</th>
                            <th class="p-3">Kapasitas</th>
                            <th class="p-3">Foto</th>
                            <th class="p-3">Booking</th>
                            <th class="p-3 pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($studios as $s)
                        <tr class="border-t">
                            <td class="p-3 pl-6">
                                <div class="flex items-center gap-3">
                                    @if($s->photos->first())
                                        <img src="{{ $s->photos->first()->url }}" class="w-14 h-10 object-cover rounded" alt="">
                                    @else
                                        <div class="w-14 h-10 bg-gray-200 rounded"></div>
                                    @endif
                                    <span class="font-medium">{{ $s->name }}</span>
                                </div>
                            </td>
                            <td class="p-3">Rp {{ number_format($s->hourly_rate, 0, ',', '.') }}</td>
                            <td class="p-3">{{ $s->capacity }} orang</td>
                            <td class="p-3">{{ $s->photos->count() }}</td>
                            <td class="p-3">{{ $s->bookings_count }}</td>
                            <td class="p-3 pr-6 text-right whitespace-nowrap">
                                <a href="{{ route('admin.studios.edit', $s) }}"
                                   class="border border-gray-300 text-gray-700 px-3 py-1.5 rounded hover:bg-gray-50">Edit</a>
                                <form method="POST" action="{{ route('admin.studios.destroy', $s) }}" class="inline"
                                      onsubmit="return confirm('Hapus studio {{ $s->name }}? Foto dan jadwalnya ikut terhapus.')">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada studio.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>