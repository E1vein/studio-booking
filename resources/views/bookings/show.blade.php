<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Booking #{{ $booking->id }}</h2>
            <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-600 hover:underline">← Riwayat Booking</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Studio</dt><dd>{{ $booking->studio->name }}</dd></div>
                    <div class="flex justify-between"><dt>Tanggal</dt><dd>{{ $booking->booking_date->translatedFormat('l, d F Y') }}</dd></div>
                    <div class="flex justify-between"><dt>Jam</dt><dd>{{ substr($booking->start_time,0,5) }} - {{ substr($booking->end_time,0,5) }}</dd></div>
                    <div class="flex justify-between font-bold"><dt>Total</dt><dd>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between items-center"><dt>Status</dt><dd><x-status-badge :status="$booking->status" /></dd></div>
                </dl>

                @if($booking->status === 'pending' && $booking->user_id === auth()->id())
                    <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 p-3 rounded">
                        Selesaikan pembayaran dalam 30 menit, atau booking otomatis dibatalkan.
                    </p>
                    <button id="pay-btn" type="button"
                            class="mt-3 w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-50">
                        Bayar Sekarang
                    </button>

                    <form id="sync-form" method="POST" action="{{ route('bookings.sync', $booking) }}">@csrf</form>

                    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
                            data-client-key="{{ config('services.midtrans.client_key') }}"></script>
                    <script>
                        const payBtn = document.getElementById('pay-btn');
                        const sync   = () => document.getElementById('sync-form').submit();

                        payBtn.addEventListener('click', async () => {
                            payBtn.disabled = true;
                            const res = await fetch(@json(route('bookings.pay', $booking)), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    Accept: 'application/json',
                                },
                            });
                            if (! res.ok) { alert('Gagal memulai pembayaran.'); payBtn.disabled = false; return; }

                            const { token } = await res.json();
                            window.snap.pay(token, {
                                onSuccess: sync,
                                onPending: sync,
                                onError:   () => { alert('Pembayaran gagal.'); payBtn.disabled = false; },
                                onClose:   () => { payBtn.disabled = false; },
                            });
                        });
                    </script>
                @endif
                
                @if($booking->user_id === auth()->id())
                    <div class="mt-4 space-y-2">
                        @if($booking->canReschedule())
                            <a href="{{ route('bookings.reschedule.edit', $booking) }}"
                            class="block text-center border border-indigo-600 text-indigo-600 px-4 py-2 rounded hover:bg-indigo-50">
                                Ubah Jadwal
                            </a>
                        @elseif(in_array($booking->status, ['pending', 'confirmed']) && $booking->reschedule_count < 1)
                            <p class="text-xs text-gray-500">Jadwal sudah tidak bisa diubah (batas maksimal H-1 sebelum sesi).</p>
                        @endif

                        @if($booking->status === 'pending')
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                                onsubmit="return confirm('Batalkan booking ini?')">
                                @csrf @method('PATCH')
                                <button class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Batalkan Booking</button>
                            </form>
                        @endif

                        @if($booking->status === 'confirmed')
                            <a href="{{ route('bookings.ics', $booking) }}"
                            class="block text-center border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">
                                📅 Tambah ke Kalender (.ics)
                            </a>
                        @endif
                    </div>
                @endif
                <div class="mt-6 flex gap-3">
                    <a href="{{ route('bookings.index') }}"
                       class="flex-1 text-center border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">
                        Riwayat Booking
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="flex-1 text-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>