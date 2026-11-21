<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $studio->name }}</h2>
            <a href="{{ route('studios.index') }}" class="text-sm text-indigo-600 hover:underline">← Katalog Studio</a>
        </div>
    </x-slot>

    <div class="py-8">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
    <div class="grid gap-8 md:grid-cols-2">
        {{-- Info studio --}}
        <div>
            <div class="rounded-lg overflow-hidden">
                <x-photo-carousel :photos="$studio->photos" height="h-72" :interval="4500" />
            </div>
            <h1 class="text-2xl font-bold mt-4">{{ $studio->name }}</h1>
            <p class="text-gray-600 mt-2">{{ $studio->description }}</p>
            <p class="mt-3">👥 Kapasitas {{ $studio->capacity }} orang</p>
            <p class="font-bold text-indigo-600 text-xl mt-1">
                Rp {{ number_format($studio->hourly_rate, 0, ',', '.') }}/jam
            </p>
        </div>

        {{-- Form booking --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-lg mb-4">Pesan Sesi</h2>

            @if($errors->any())
                <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                    @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.store') }}" id="booking-form">
                @csrf
                <input type="hidden" name="studio_id" value="{{ $studio->id }}">
                <input type="hidden" name="start_time" id="start_time">
                <input type="hidden" name="end_time" id="end_time">

                <label class="block text-sm font-medium mb-1">Tanggal</label>
                <input type="date" name="booking_date" id="booking_date"
                       min="{{ today()->toDateString() }}" value="{{ old('booking_date') }}"
                       class="w-full border-gray-300 rounded mb-4" required>

                <label class="block text-sm font-medium mb-1">Durasi</label>
                <select id="duration" class="w-full border-gray-300 rounded mb-4">
                    @foreach([1,2,3,4,5,6] as $h)
                        <option value="{{ $h }}">{{ $h }} jam</option>
                    @endforeach
                </select>

                <label class="block text-sm font-medium mb-1">Jam mulai</label>
                <div id="slots" class="grid grid-cols-4 gap-2 mb-4 text-sm">
                    <p class="col-span-4 text-gray-400">Pilih tanggal dulu.</p>
                </div>

                <div class="border-t pt-4 mb-4 text-sm">
                    <div class="flex justify-between"><span>Waktu</span><span id="sum-time">-</span></div>
                    <div class="flex justify-between font-bold text-base mt-1">
                        <span>Estimasi total</span><span id="sum-price">-</span>
                    </div>
                </div>

                @auth
                    <button id="submit-btn" disabled
                        class="w-full bg-indigo-600 text-white py-2 rounded disabled:opacity-40 disabled:cursor-not-allowed">
                        Lanjut Booking
                    </button>
                @else
                    <a href="{{ route('login') }}" class="block text-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Login untuk booking
                    </a>
                @endauth
            </form>
        </div>
    </div>
    </div>
    </div>

    <script>
        const schedules = @json($studio->schedules->keyBy('day_of_week'));
        const slotsUrl  = @json(route('studios.slots', $studio));
        const rate      = {{ $studio->hourly_rate }};

        const dateEl = document.getElementById('booking_date');
        const durEl  = document.getElementById('duration');
        const slotsEl = document.getElementById('slots');
        const startEl = document.getElementById('start_time');
        const endEl   = document.getElementById('end_time');
        const btn     = document.getElementById('submit-btn');

        let booked = [];   // [{start: 14, end: 16}, ...] dalam jam (desimal)

        const toHour = t => { const [h, m] = t.split(':'); return +h + (+m / 60); };
        const pad    = n => String(n).padStart(2, '0') + ':00';
        const rupiah = n => 'Rp ' + n.toLocaleString('id-ID');

        function resetSelection() {
            startEl.value = endEl.value = '';
            document.getElementById('sum-time').textContent = '-';
            document.getElementById('sum-price').textContent = '-';
            if (btn) btn.disabled = true;
        }

        async function loadSlots() {
            resetSelection();
            if (!dateEl.value) return;

            const res = await fetch(`${slotsUrl}?date=${dateEl.value}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            booked = data.map(b => ({ start: toHour(b.start_time), end: toHour(b.end_time) }));
            renderSlots();
        }

        function renderSlots() {
            const date = new Date(dateEl.value + 'T00:00:00');
            const sch  = schedules[date.getDay()];
            slotsEl.innerHTML = '';

            if (!sch) {
                slotsEl.innerHTML = '<p class="col-span-4 text-red-500">Studio tutup di hari ini.</p>';
                return;
            }

            const open = toHour(sch.open_time), close = toHour(sch.close_time);
            const dur  = +durEl.value;
            const now  = new Date();
            const isToday = dateEl.value === now.toISOString().slice(0, 10);
            const nowHour = now.getHours() + now.getMinutes() / 60;

            for (let h = open; h + dur <= close; h++) {
                const end = h + dur;
                const clash = booked.some(b => b.start < end && b.end > h);
                const past  = isToday && h <= nowHour;
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = pad(h);
                b.disabled = clash || past;
                b.className = 'py-2 rounded border ' + (b.disabled
                    ? 'bg-gray-100 text-gray-400 line-through cursor-not-allowed'
                    : 'hover:bg-indigo-50 border-indigo-300');
                b.onclick = () => selectSlot(h, end, b);
                slotsEl.appendChild(b);
            }

            if (!slotsEl.children.length) {
                slotsEl.innerHTML = '<p class="col-span-4 text-gray-500">Tidak ada slot dengan durasi ini.</p>';
            }
        }

        function selectSlot(start, end, el) {
            slotsEl.querySelectorAll('button').forEach(x => x.classList.remove('bg-indigo-600', 'text-white'));
            el.classList.add('bg-indigo-600', 'text-white');
            startEl.value = pad(start);
            endEl.value   = pad(end);
            document.getElementById('sum-time').textContent = `${pad(start)} - ${pad(end)}`;
            document.getElementById('sum-price').textContent = rupiah((end - start) * rate);
            if (btn) btn.disabled = false;
        }

        dateEl.addEventListener('change', loadSlots);
        durEl.addEventListener('change', () => { resetSelection(); if (dateEl.value) renderSlots(); });
        if (dateEl.value) loadSlots();
    </script>
</x-app-layout>