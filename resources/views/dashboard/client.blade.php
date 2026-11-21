<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Sambutan --}}
            <div class="bg-white rounded-lg shadow p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold">Halo, {{ auth()->user()->name }} 👋</h3>
                    <p class="text-sm text-gray-600">Siap bikin sesi berikutnya?</p>
                </div>
                <a href="{{ route('studios.index') }}"
                   class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-center">
                    + Booking Studio
                </a>
            </div>

            {{-- Statistik --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['Total Booking', $stats['total']],
                    ['Sesi Mendatang', $stats['upcoming']],
                    ['Menunggu Bayar', $stats['pending']],
                    ['Total Pengeluaran', 'Rp '.number_format($stats['spent'], 0, ',', '.')],
                ] as [$label, $value])
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">{{ $label }}</div>
                        <div class="text-xl font-bold mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Sesi terdekat --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-3">Sesi Terdekat</h3>
                @if($next)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <div class="font-medium">{{ $next->studio->name }}</div>
                            <div class="text-sm text-gray-600">
                                {{ $next->booking_date->translatedFormat('l, d F Y') }} ·
                                {{ substr($next->start_time, 0, 5) }} - {{ substr($next->end_time, 0, 5) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-status-badge :status="$next->status" />
                            <a href="{{ route('bookings.show', $next) }}" class="text-indigo-600 text-sm hover:underline">Detail</a>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Belum ada sesi yang dijadwalkan.</p>
                @endif
            </div>

            {{-- Riwayat singkat --}}
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 pb-3 flex justify-between items-center">
                    <h3 class="font-semibold">Booking Terakhir</h3>
                    <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody>
                        @forelse($recent as $b)
                            <tr class="border-t">
                                <td class="p-3 pl-6">{{ $b->studio->name }}</td>
                                <td class="p-3">{{ $b->booking_date->translatedFormat('d M Y') }}</td>
                                <td class="p-3">{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
                                <td class="p-3"><x-status-badge :status="$b->status" /></td>
                                <td class="p-3 pr-6 text-right">
                                    <a href="{{ route('bookings.show', $b) }}" class="text-indigo-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="p-6 text-center text-gray-500">Belum ada booking.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>