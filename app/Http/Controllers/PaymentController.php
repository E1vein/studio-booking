<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Dipanggil tombol "Bayar Sekarang" -> mengembalikan Snap token
    public function token(Booking $booking, PaymentService $payments)
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_unless($booking->status === 'pending', 422, 'Booking ini tidak menunggu pembayaran.');

        return response()->json(['token' => $payments->snapToken($booking)]);
    }

    // Dipanggil setelah popup Snap ditutup/selesai
    public function sync(Booking $booking, PaymentService $payments)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $payments->syncFromMidtrans($booking);
        $booking->refresh();

        return redirect()->route('bookings.show', $booking)->with(
            $booking->status === 'confirmed' ? 'success' : 'error',
            $booking->status === 'confirmed'
                ? 'Pembayaran diterima, booking dikonfirmasi!'
                : 'Pembayaran belum selesai. Status akan diperbarui otomatis setelah dibayar.'
        );
    }

    // Webhook dari Midtrans (tanpa login & tanpa CSRF, jadi WAJIB verifikasi signature)
    public function notification(Request $request, PaymentService $payments)
    {
        $p = $request->all();

        $expected = hash('sha512',
            ($p['order_id'] ?? '') . ($p['status_code'] ?? '') . ($p['gross_amount'] ?? '')
            . config('services.midtrans.server_key')
        );
        abort_unless(hash_equals($expected, (string) ($p['signature_key'] ?? '')), 403);

        $booking = Booking::where('order_id', $p['order_id'] ?? '')->first();

        // Pastikan nominal cocok dengan data kita
        if ($booking && (int) round((float) $p['gross_amount']) === $booking->total_price) {
            $payments->applyStatus(
                $booking,
                $p['transaction_status'] ?? '',
                $p['fraud_status'] ?? null,
                $p['payment_type'] ?? null
            );
        }

        return response()->json(['ok' => true]);
    }
}