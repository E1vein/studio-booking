<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Mail\BookingConfirmed;
use Illuminate\Support\Facades\Mail;

/**
 * @property int $id
 * @property int $user_id
 * @property int $studio_id
 * @property Carbon $booking_date
 * @property string $start_time
 * @property string $end_time
 * @property int $total_price
 * @property string $status
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Studio $studio
 */

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'studio_id', 'booking_date', 'start_time', 'end_time',
        'total_price', 'status',
        'order_id', 'snap_token', 'payment_type', 'paid_at', 'reschedule_count',
    ];

    protected $casts = ['booking_date' => 'date',
                        'paid_at'      => 'datetime',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function studio() { return $this->belongsTo(Studio::class); }

// Booking yang masih "memblokir" slot
    public function scopeActive($q) { return $q->whereIn('status', ['pending', 'confirmed']); }

// Rumus bentrok: existing.start < new.end AND existing.end > new.start
    public function scopeOverlapping($q, string $date, string $start, string $end)
    {
        return $q->whereDate('booking_date', $date)
                 ->where('start_time', '<', $end)
                 ->where('end_time', '>', $start);
    }
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->start_time);
    }
    public function canReschedule(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->reschedule_count < 1
            && $this->startsAt()->gt(now()->addDay());
    }

    public function notifyConfirmed(bool $rescheduled = false): void
    {
        try {
            Mail::to($this->user)->send(new BookingConfirmed($this, $rescheduled));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
