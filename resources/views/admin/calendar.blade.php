<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender Agenda</h2>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-600"></span> Pending</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-600"></span> Confirmed</span>
                <select id="studio-filter" class="border-gray-300 rounded text-sm">
                    <option value="">Semua studio</option>
                    @foreach($studios as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    {{-- Modal detail booking --}}
    <div id="modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6">
            <h3 class="font-semibold text-lg mb-3" id="m-title"></h3>
            <dl class="space-y-1 text-sm mb-4">
                <div class="flex justify-between"><dt>Pemesan</dt><dd id="m-user"></dd></div>
                <div class="flex justify-between"><dt>Waktu</dt><dd id="m-time"></dd></div>
                <div class="flex justify-between"><dt>Total</dt><dd id="m-price"></dd></div>
                <div class="flex justify-between"><dt>Status</dt><dd id="m-status"></dd></div>
            </dl>
            <form id="m-form" method="POST" class="flex gap-2">
                @csrf @method('PATCH')
                <button name="status" value="confirmed" class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Konfirmasi</button>
                <button name="status" value="cancelled" onclick="return confirm('Batalkan booking ini?')"
                        class="flex-1 bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Batalkan</button>
            </form>
            <button type="button" id="m-close" class="mt-3 w-full border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm hover:bg-gray-50">Tutup</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/id.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const eventsUrl = @json(route('admin.calendar.events'));
            const statusUrl = @json(route('admin.bookings.status', ['booking' => '__ID__']));
            const filter = document.getElementById('studio-filter');
            const modal  = document.getElementById('modal');

            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                locale: 'id',
                firstDay: 1,
                height: 'auto',
                nowIndicator: true,
                initialView: window.innerWidth < 640 ? 'timeGridDay' : 'timeGridWeek',
                slotMinTime: '07:00:00',
                slotMaxTime: '23:00:00',
                allDaySlot: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                events: (info, success, failure) => {
                    const q = new URLSearchParams({ start: info.startStr, end: info.endStr, studio_id: filter.value });
                    fetch(`${eventsUrl}?${q}`, { headers: { Accept: 'application/json' } })
                        .then(r => r.json()).then(success).catch(failure);
                },
                eventClick: ({ event }) => {
                    const p = event.extendedProps;
                    document.getElementById('m-title').textContent  = p.studio;
                    document.getElementById('m-user').textContent   = p.user;
                    document.getElementById('m-time').textContent   = p.time;
                    document.getElementById('m-price').textContent  = p.price;
                    document.getElementById('m-status').textContent = p.status;
                    document.getElementById('m-form').action = statusUrl.replace('__ID__', event.id);
                    modal.classList.remove('hidden');
                },
            });

            calendar.render();
            filter.addEventListener('change', () => calendar.refetchEvents());
            document.getElementById('m-close').onclick = () => modal.classList.add('hidden');
            modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });
        });
    </script>
</x-app-layout>