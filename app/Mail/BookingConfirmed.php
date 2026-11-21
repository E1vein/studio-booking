<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\IcsGenerator;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmed extends Mailable
{
    public function __construct(public Booking $booking, public bool $rescheduled = false)
    {
        $booking->loadMissing(['studio', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rescheduled
                ? "Jadwal booking #{$this->booking->id} diperbarui"
                : "Booking #{$this->booking->id} dikonfirmasi",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.booking-confirmed');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => IcsGenerator::make($this->booking), "booking-{$this->booking->id}.ics")
                ->withMime('text/calendar'),
        ];
    }
}