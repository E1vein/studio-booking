<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Statistik --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['Booking Hari Ini', $stats['today']],
                    ['Perlu Diverifikasi', $stats['pending']],
                    ['Pendapatan Bulan Ini', 'Rp '.number_format($stats['revenue'], 0, ',', '.')],
                    ['Jumlah Studio', $stats['studios']],
                ] as [$label, $value])
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-xs text-gray-500">{{ $label }}</div>
                        <div class="text-xl font-bold mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Agenda hari ini --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold mb-3">
                        Agenda Hari Ini
                        <span class="text-xs text-gray-500 font-normal">({{ today()->translatedFormat('d F Y') }})</span>
                    </h3>
                    <ul class="divide-y text-sm">
                        @forelse($todayBookings as $b)
                            <li class="py-3 flex justify-between gap-3">
                                <div>
                                    <div class="font-medium">
                                        {{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }} · {{ $b->studio->name }}
                                    </div>
                                    <div class="text-gray-500">{{ $b->user->name }}</div>
                                </div>
                                <x-status-badge :status="$b->status" />
                            </li>
                        @empty
                            <li class="py-6 text-center text-gray-500">Tidak ada sesi hari ini.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Menunggu verifikasi --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold mb-3">Menunggu Verifikasi</h3>
                    <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                    <ul class="divide-y text-sm">
                        @forelse($pending as $b)
                            <li class="py-3">
                                <div class="font-medium">{{ $b->studio->name }} · {{ $b->user->name }}</div>
                                <div class="text-gray-500 mb-2">
                                    {{ $b->booking_date->translatedFormat('d M Y') }},
                                    {{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}
                                    · Rp {{ number_format($b->total_price, 0, ',', '.') }}
                                </div>
                                @include('admin.bookings._actions', ['booking' => $b])
                            </li>
                        @empty
                            <li class="py-6 text-center text-gray-500">Semua booking sudah beres 🎉</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>