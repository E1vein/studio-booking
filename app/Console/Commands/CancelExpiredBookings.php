<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    protected $signature = 'bookings:cancel-expired';
    protected $description = 'Batalkan booking pending yang tidak dibayar dalam 30 menit';

    public function handle(): void
    {
        $count = \App\Models\Booking::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->update(['status' => 'cancelled']);

        $this->info("$count booking dibatalkan.");
    }
}
