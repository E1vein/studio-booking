<x-mail::message>
# {{ $rescheduled ? 'Jadwal Diperbarui 🔄' : 'Booking Dikonfirmasi ✅' }}

Halo {{ $booking->user->name }},

{{ $rescheduled ? 'Jadwal sesimu sudah diubah.' : 'Pembayaranmu sudah kami terima.' }} Berikut detail sesimu:

<x-mail::panel>
**Studio:** {{ $booking->studio->name }}<br>
**Tanggal:** {{ $booking->booking_date->translatedFormat('l, d F Y') }}<br>
**Jam:** {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}<br>
**Total:** Rp {{ number_format($booking->total_price, 0, ',', '.') }}
</x-mail::panel>

File kalender (.ics) terlampir, buka untuk menambahkan sesi ke Google Calendar atau Apple Calendar.

<x-mail::button :url="route('bookings.show', $booking)">
Lihat Detail Booking
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>