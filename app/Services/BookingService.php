<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Studio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(int $userId, Studio $studio, string $date, string $start, string $end): Booking
    {
        $startAt = Carbon::parse("$date $start");
        $endAt   = Carbon::parse("$date $end");

        if ($endAt->lte($startAt)) {
            throw ValidationException::withMessages(['end_time' => 'Jam selesai harus setelah jam mulai.']);
        }
        if ($startAt->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Tidak bisa booking di waktu yang sudah lewat.']);
        }

        // 1. Cek jam operasional
        $schedule = $studio->schedules()->where('day_of_week', $startAt->dayOfWeek)->first();
        if (!$schedule
            || $start < substr($schedule->open_time, 0, 5)
            || $end > substr($schedule->close_time, 0, 5)) {
            throw ValidationException::withMessages(['start_time' => 'Di luar jam operasional studio.']);
        }

        // 2. Hitung harga di backend (bukan dari frontend)
        $hours = $startAt->diffInMinutes($endAt) / 60;
        $total = (int) round($hours * $studio->hourly_rate);

        // 3. Transaksi + lock supaya tidak ada race condition
        return DB::transaction(function () use ($userId, $studio, $date, $start, $end, $total) {
            Studio::whereKey($studio->id)->lockForUpdate()->first();

            $conflict = Booking::where('studio_id', $studio->id)
                ->active()
                ->overlapping($date, $start, $end)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages(['start_time' => 'Slot sudah dipesan, pilih jam lain.']);
            }

            return Booking::create([
                'user_id'      => $userId,
                'studio_id'    => $studio->id,
                'booking_date' => $date,
                'start_time'   => $start,
                'end_time'     => $end,
                'total_price'  => $total,
                'status'       => 'pending',
            ]);
        });
    }

    // Untuk UI kalender: daftar jam yang sudah terisi di tanggal tertentu
    // GANTI method bookedSlots yang lama
    public function bookedSlots(Studio $studio, string $date, ?int $excludeId = null)
    {
        return $studio->bookings()->active()
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->whereDate('booking_date', $date)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);
    }

    // TAMBAHKAN
    public function reschedule(Booking $booking, string $date, string $start): Booking
    {
        $minutes = Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time));

        $startAt = Carbon::parse("$date $start");
        $endAt   = $startAt->copy()->addMinutes($minutes);   // durasi & harga tetap sama
        $end     = $endAt->format('H:i');

        if ($startAt->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Tidak bisa memilih waktu yang sudah lewat.']);
        }
        if (! $endAt->isSameDay($startAt)) {
            throw ValidationException::withMessages(['start_time' => 'Sesi tidak boleh melewati tengah malam.']);
        }

        $schedule = $booking->studio->schedules()->where('day_of_week', $startAt->dayOfWeek)->first();
        if (! $schedule || $start < substr($schedule->open_time, 0, 5) || $end > substr($schedule->close_time, 0, 5)) {
            throw ValidationException::withMessages(['start_time' => 'Di luar jam operasional studio.']);
        }

        return DB::transaction(function () use ($booking, $date, $start, $end) {
            Studio::whereKey($booking->studio_id)->lockForUpdate()->first();
            $booking->refresh();

            if (! $booking->canReschedule()) {
                throw ValidationException::withMessages([
                    'booking_date' => 'Reschedule hanya bisa dilakukan maksimal H-1 (24 jam sebelum sesi) dan satu kali.',
                ]);
            }

            $conflict = Booking::active()
                ->where('studio_id', $booking->studio_id)
                ->whereKeyNot($booking->id)
                ->overlapping($date, $start, $end)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages(['start_time' => 'Slot sudah dipesan, pilih jam lain.']);
            }

            $booking->update([
                'booking_date'     => $date,
                'start_time'       => $start,
                'end_time'         => $end,
                'reschedule_count' => $booking->reschedule_count + 1,
            ]);

            return $booking;
        });
    }
}