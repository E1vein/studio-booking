<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ubah Jadwal Booking #{{ $booking->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-600 mb-1">{{ $booking->studio->name }}</p>
                <p class="text-sm mb-4">
                    Jadwal sekarang:
                    <strong>{{ $booking->booking_date->translatedFormat('d M Y') }},
                    {{ substr($booking->start_time,0,5) }} - {{ substr($booking->end_time,0,5) }}</strong>
                    <span class="block text-gray-500" id="dur-info"></span>
                </p>

                @if($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('bookings.reschedule.update', $booking) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="start_time" id="start_time">

                    <label class="block text-sm font-medium mb-1">Tanggal baru</label>
                    <input type="date" name="booking_date" id="booking_date" min="{{ today()->toDateString() }}"
                           value="{{ old('booking_date') }}" class="w-full border-gray-300 rounded mb-4" required>

                    <label class="block text-sm font-medium mb-1">Jam mulai baru</label>
                    <div id="slots" class="grid grid-cols-4 gap-2 mb-4 text-sm">
                        <p class="col-span-4 text-gray-400">Pilih tanggal dulu.</p>
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('bookings.show', $booking) }}"
                           class="flex-1 text-center border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">Batal</a>
                        <button id="submit-btn" disabled
                                class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const schedules = @json($booking->studio->schedules->keyBy('day_of_week'));
        const slotsUrl  = @json(route('studios.slots', $booking->studio));
        const excludeId = {{ $booking->id }};
        const toHour = t => { const [h, m] = t.split(':'); return +h + (+m / 60); };
        const dur    = toHour(@json($booking->end_time)) - toHour(@json($booking->start_time));
        const pad    = n => String(n).padStart(2, '0') + ':00';

        const dateEl = document.getElementById('booking_date');
        const slotsEl = document.getElementById('slots');
        const startEl = document.getElementById('start_time');
        const btn = document.getElementById('submit-btn');
        document.getElementById('dur-info').textContent = `Durasi tetap ${dur} jam, harga tidak berubah.`;

        dateEl.addEventListener('change', async () => {
            startEl.value = ''; btn.disabled = true;
            if (!dateEl.value) return;

            const res = await fetch(`${slotsUrl}?date=${dateEl.value}&exclude=${excludeId}`, { headers: { Accept: 'application/json' } });
            const booked = (await res.json()).map(b => ({ start: toHour(b.start_time), end: toHour(b.end_time) }));

            const sch = schedules[new Date(dateEl.value + 'T00:00:00').getDay()];
            slotsEl.innerHTML = '';
            if (!sch) { slotsEl.innerHTML = '<p class="col-span-4 text-red-500">Studio tutup di hari ini.</p>'; return; }

            const now = new Date();
            const isToday = dateEl.value === now.toISOString().slice(0, 10);
            const nowHour = now.getHours() + now.getMinutes() / 60;

            for (let h = toHour(sch.open_time); h + dur <= toHour(sch.close_time); h++) {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = pad(h);
                b.disabled = booked.some(x => x.start < h + dur && x.end > h) || (isToday && h <= nowHour);
                b.className = 'py-2 rounded border ' + (b.disabled
                    ? 'bg-gray-100 text-gray-400 line-through cursor-not-allowed'
                    : 'hover:bg-indigo-50 border-indigo-300');
                b.onclick = () => {
                    slotsEl.querySelectorAll('button').forEach(x => x.classList.remove('bg-indigo-600', 'text-white'));
                    b.classList.add('bg-indigo-600', 'text-white');
                    startEl.value = pad(h); btn.disabled = false;
                };
                slotsEl.appendChild(b);
            }
        });
    </script>
</x-app-layout>