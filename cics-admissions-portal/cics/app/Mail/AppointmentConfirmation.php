<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Step 4: softcopy of Stamp, Reference Number, Requirements and Reminders. */
class AppointmentConfirmation extends Mailable
{
    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "CICS {$this->appointment->label} Slip — {$this->appointment->reference_no}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-slip');
    }
}
