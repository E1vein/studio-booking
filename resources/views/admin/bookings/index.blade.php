<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Booking</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter --}}
            <form method="GET" class="bg-white rounded-lg shadow p-4 grid gap-3 sm:grid-cols-4 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded text-sm">
                        <option value="">Semua</option>
                        @foreach(['pending', 'confirmed', 'cancelled'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Studio</label>
                    <select name="studio_id" class="w-full border-gray-300 rounded text-sm">
                        <option value="">Semua</option>
                        @foreach($studios as $s)
                            <option value="{{ $s->id }}" @selected(request('studio_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="w-full border-gray-300 rounded text-sm">
                </div>
                <div class="flex gap-2">
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">Filter</button>
                    <a href="{{ route('admin.bookings.index') }}"
                       class="border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">Reset</a>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="p-3 pl-6">#</th><th class="p-3">Pemesan</th><th class="p-3">Studio</th>
                            <th class="p-3">Jadwal</th><th class="p-3">Total</th><th class="p-3">Status</th>
                            <th class="p-3 pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $b)
                        <tr class="border-t">
                            <td class="p-3 pl-6">{{ $b->id }}</td>
                            <td class="p-3">{{ $b->user->name }}<div class="text-xs text-gray-500">{{ $b->user->email }}</div></td>
                            <td class="p-3">{{ $b->studio->name }}</td>
                            <td class="p-3">
                                {{ $b->booking_date->translatedFormat('d M Y') }}<br>
                                <span class="text-gray-500">{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</span>
                            </td>
                            <td class="p-3">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                            <td class="p-3"><x-status-badge :status="$b->status" /></td>
                            <td class="p-3 pr-6 text-right">@include('admin.bookings._actions', ['booking' => $b])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-gray-500">Tidak ada booking.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $bookings->links() }}
        </div>
    </div>
</x-app-layout>