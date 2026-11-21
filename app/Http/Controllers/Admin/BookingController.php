<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Studio;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['studio', 'user'])
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            ->when($request->studio_id, fn ($q, $v) => $q->where('studio_id', $v))
            ->when($request->date,      fn ($q, $v) => $q->whereDate('booking_date', $v))
            ->orderByDesc('booking_date')->orderByDesc('start_time')
            ->paginate(15)->withQueryString();

        $studios = Studio::orderBy('name')->get(['id', 'name']);

        return view('admin.bookings.index', compact('bookings', 'studios'));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $status = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled'],
        ])['status'];

        // Mengaktifkan kembali booking yang dibatalkan: pastikan slotnya belum diambil orang lain
        if ($booking->status === 'cancelled' && $status !== 'cancelled') {
            $conflict = Booking::active()
                ->where('studio_id', $booking->studio_id)
                ->whereKeyNot($booking->id)
                ->overlapping($booking->booking_date->toDateString(), $booking->start_time, $booking->end_time)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Slot ini sudah dipesan booking lain, tidak bisa diaktifkan kembali.');
            }
        }

        $wasConfirmed = $booking->status === 'confirmed';
        $booking->update(['status' => $status]);

        if ($status === 'confirmed' && ! $wasConfirmed) {
            $booking->notifyConfirmed();
        }

        return back()->with('success', "Booking #{$booking->id} diubah menjadi {$status}.");
    }

    public function calendar()
    {
        $studios = Studio::orderBy('name')->get(['id', 'name']);
        return view('admin.calendar', compact('studios'));
    }

    // Dipanggil FullCalendar (JSON)
    public function events(Request $request)
    {
        $from = Carbon::parse($request->query('start'))->toDateString();
        $to   = Carbon::parse($request->query('end'))->toDateString();

        return Booking::with(['studio', 'user'])
            ->active()
            ->whereBetween('booking_date', [$from, $to])
            ->when($request->studio_id, fn ($q, $v) => $q->where('studio_id', $v))
            ->get()
            ->map(function ($b) {
                $date = $b->booking_date->toDateString();
                return [
                    'id'    => $b->id,
                    'title' => $b->studio->name . ' · ' . $b->user->name,
                    'start' => $date . 'T' . $b->start_time,
                    'end'   => $date . 'T' . $b->end_time,
                    'color' => $b->status === 'confirmed' ? '#16a34a' : '#ca8a04',
                    'extendedProps' => [
                        'studio' => $b->studio->name,
                        'user'   => $b->user->name,
                        'status' => $b->status,
                        'time'   => substr($b->start_time, 0, 5) . ' - ' . substr($b->end_time, 0, 5),
                        'price'  => 'Rp ' . number_format($b->total_price, 0, ',', '.'),
                    ],
                ];
            });
    }
}