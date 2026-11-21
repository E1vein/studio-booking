<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return $user->isAdmin()
            ? $this->admin()
            : $this->client($user);
    }

    private function client(User $user)
    {
        $upcoming = $user->bookings()->with('studio')
            ->active()
            ->whereDate('booking_date', '>=', today())
            ->orderBy('booking_date')->orderBy('start_time')
            ->get();

        $stats = [
            'total'     => $user->bookings()->count(),
            'upcoming'  => $upcoming->count(),
            'pending'   => $user->bookings()->where('status', 'pending')->count(),
            'spent'     => (int) $user->bookings()->where('status', 'confirmed')->sum('total_price'),
        ];

        $recent = $user->bookings()->with('studio')->latest()->limit(5)->get();

        return view('dashboard.client', [
            'stats'    => $stats,
            'next'     => $upcoming->first(),
            'recent'   => $recent,
        ]);
    }

    private function admin()
    {
        $todayBookings = Booking::with(['studio', 'user'])
            ->active()
            ->whereDate('booking_date', today())
            ->orderBy('start_time')
            ->get();

        $stats = [
            'today'    => $todayBookings->count(),
            'pending'  => Booking::where('status', 'pending')->count(),
            'revenue'  => (int) Booking::where('status', 'confirmed')
                            ->whereMonth('booking_date', now()->month)
                            ->whereYear('booking_date', now()->year)
                            ->sum('total_price'),
            'studios'  => Studio::count(),
        ];

        $pending = Booking::with(['studio', 'user'])
            ->where('status', 'pending')
            ->latest()->limit(5)->get();

        return view('dashboard.admin', compact('stats', 'todayBookings', 'pending'));
    }
}