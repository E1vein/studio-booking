<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Booking</h2>
            <a href="{{ route('studios.index') }}"
               class="bg-indigo-600 text-white text-sm px-4 py-2 rounded hover:bg-indigo-700">
                + Booking Studio
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="p-3 pl-6">Studio</th>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Jam</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 pr-6"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $b)
                        <tr class="border-t">
                            <td class="p-3 pl-6">{{ $b->studio->name }}</td>
                            <td class="p-3">{{ $b->booking_date->translatedFormat('d M Y') }}</td>
                            <td class="p-3">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                            <td class="p-3">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                            <td class="p-3"><x-status-badge :status="$b->status" /></td>
                            <td class="p-3 pr-6 text-right">
                                <a href="{{ route('bookings.show', $b) }}" class="text-indigo-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada booking.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>