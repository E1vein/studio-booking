<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;

class IcsGenerator
{
    public static function make(Booking $b): string
    {
        $b->loadMissing('studio');

        $tz    = config('app.timezone');
        $date  = $b->booking_date->toDateString();
        $start = Carbon::parse("$date {$b->start_time}", $tz);
        $end   = Carbon::parse("$date {$b->end_time}", $tz);

        $utc = fn (Carbon $c) => $c->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn (string $s) => str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $s);
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'studio-booking.local';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Studio Booking//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:booking-{$b->id}@{$host}",          // UID tetap -> reschedule meng-update event lama
            "SEQUENCE:{$b->reschedule_count}",
            'DTSTAMP:' . $utc(now()),
            'DTSTART:' . $utc($start),
            'DTEND:'   . $utc($end),
            'SUMMARY:' . $esc("Sesi {$b->studio->name}"),
            'DESCRIPTION:' . $esc("Booking #{$b->id} di {$b->studio->name}."),
            'LOCATION:' . $esc($b->studio->name),
            'BEGIN:VALARM',
            'TRIGGER:-PT1H',
            'ACTION:DISPLAY',
            'DESCRIPTION:Sesi studio dimulai 1 jam lagi',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }
}