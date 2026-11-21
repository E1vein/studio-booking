<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class PaymentService
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    public function snapToken(Booking $booking): string
    {
        if ($booking->snap_token) {
            return $booking->snap_token;   // token yang sama bisa dipakai ulang sampai kedaluwarsa
        }

        $booking->loadMissing(['user', 'studio']);
        $orderId = 'BOOK-' . $booking->id . '-' . now()->timestamp;
        $label   = $booking->studio->name . ' ' . $booking->booking_date->format('d/m') . ' ' . substr($booking->start_time, 0, 5);

        $token = Snap::getSnapToken([
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
            ],
            'item_details' => [[
                'id'       => 'studio-' . $booking->studio_id,
                'price'    => $booking->total_price,
                'quantity' => 1,
                'name'     => Str::limit($label, 50, ''),   // batas Midtrans: 50 karakter
            ]],
            'expiry' => ['unit' => 'minute', 'duration' => 30],   // selaras dengan auto-cancel 30 menit
        ]);

        $booking->update(['order_id' => $orderId, 'snap_token' => $token]);

        return $token;
    }

    // Tanya langsung ke Midtrans (dipakai setelah popup bayar ditutup, tanpa perlu webhook publik)
    public function syncFromMidtrans(Booking $booking): void
    {
        if (! $booking->order_id) {
            return;
        }

        try {
            $r = (array) Transaction::status($booking->order_id);
        } catch (\Throwable $e) {
            return;   // transaksi belum tercatat di Midtrans
        }

        $this->applyStatus($booking, $r['transaction_status'] ?? '', $r['fraud_status'] ?? null, $r['payment_type'] ?? null);
    }

    // Satu pintu untuk mengubah status booking dari hasil pembayaran (dipakai webhook DAN sync)
    public function applyStatus(Booking $b, string $status, ?string $fraud = null, ?string $type = null): void
    {
        $paid = $status === 'settlement'
            || ($status === 'capture' && ($fraud === null || $fraud === 'accept'));

        if ($paid) {
            if ($b->status === 'confirmed') {
                return;   // idempoten: notifikasi ganda tidak masalah
            }
            if ($b->status === 'cancelled' && $this->slotTaken($b)) {
                Log::warning("Booking #{$b->id} dibayar setelah dibatalkan dan slotnya sudah terisi. Perlu refund manual.");
                return;
            }

            $b->update(['status' => 'confirmed', 'paid_at' => now(), 'payment_type' => $type]);
            $b->notifyConfirmed();
            return;
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure']) && $b->status === 'pending') {
            $b->update(['status' => 'cancelled']);
        }
    }

    // Batalkan transaksi di sisi Midtrans supaya tidak bisa dibayar setelah booking dibatalkan
    public function cancelTransaction(Booking $b): void
    {
        if (! $b->order_id) {
            return;
        }
        try {
            Transaction::cancel($b->order_id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function slotTaken(Booking $b): bool
    {
        return Booking::active()
            ->where('studio_id', $b->studio_id)
            ->whereKeyNot($b->id)
            ->overlapping($b->booking_date->toDateString(), $b->start_time, $b->end_time)
            ->exists();
    }
}