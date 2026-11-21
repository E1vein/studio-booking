<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studio extends Model
{
    protected $fillable = ['name', 'description', 'hourly_rate', 'capacity'];

    public function schedules() { return $this->hasMany(Schedule::class); }
    public function bookings()  { return $this->hasMany(Booking::class); }
    public function photos()
    {
        return $this->hasMany(StudioPhoto::class)->orderBy('sort_order');
    }
}
