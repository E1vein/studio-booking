<form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="inline-flex gap-2">
    @csrf @method('PATCH')
    @if($booking->status !== 'confirmed')
        <button name="status" value="confirmed"
                class="bg-green-600 text-white text-xs px-3 py-1.5 rounded hover:bg-green-700">Konfirmasi</button>
    @endif
    @if($booking->status !== 'cancelled')
        <button name="status" value="cancelled" onclick="return confirm('Batalkan booking ini?')"
                class="bg-red-600 text-white text-xs px-3 py-1.5 rounded hover:bg-red-700">Batalkan</button>
    @endif
</form>