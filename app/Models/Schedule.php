<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public $timestamps = false;
    protected $fillable = ['studio_id', 'day_of_week', 'open_time', 'close_time'];
}
