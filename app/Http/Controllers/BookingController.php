<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Studio;
use App\Services\BookingService;
use App\Support\IcsGenerator;
use App\Services\PaymentService;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, BookingService $service)
    {
        $studio = Studio::findOrFail($request->studio_id);

        $booking = $service->create(
            auth()->id(), $studio,
            $request->booking_date, $request->start_time, $request->end_time
        );

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Booking dibuat, silakan lanjut pembayaran.');
    }

    public function index()
    {
        $bookings = auth()->user()->bookings()->with('studio')->latest()->get();
        return view('bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        return view('bookings.show', compact('booking'));
    }   

// Endpoint JSON untuk slot picker (dipanggil via fetch dari frontend)
    public function slots(Studio $studio, Request $request, BookingService $service)
    {
        return $service->bookedSlots(
            $studio,
            $request->query('date', today()->toDateString()),
            $request->integer('exclude') ?: null
        );
    }

    public function rescheduleForm(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        if (! $booking->canReschedule()) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Reschedule hanya bisa maksimal H-1 dan satu kali.');
        }

        $booking->load('studio.schedules');
        return view('bookings.reschedule', compact('booking'));
    }

    public function reschedule(Request $request, Booking $booking, BookingService $service)
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i'],
        ]);

        $service->reschedule($booking, $data['booking_date'], $data['start_time']);

        if ($booking->status === 'confirmed') {
            $booking->notifyConfirmed(rescheduled: true);   // email + .ics baru (UID sama, SEQUENCE naik)
        }

        return redirect()->route('bookings.show', $booking)->with('success', 'Jadwal berhasil diubah.');
    }

    // Pembatalan oleh user: hanya untuk booking yang belum dibayar
    public function cancel(Booking $booking, PaymentService $payments)
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_unless($booking->status === 'pending', 422);

        $payments->cancelTransaction($booking);
        $booking->update(['status' => 'cancelled']);

        return redirect()->route('bookings.index')->with('success', 'Booking dibatalkan.');
    }

    public function ics(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        abort_unless($booking->status === 'confirmed', 404);

        return response(IcsGenerator::make($booking), 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"booking-{$booking->id}.ics\"",
        ]);
    }
}
