<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotifikasiSurat extends Mailable
{
    public function __construct(public string $subjek, public string $judul, public string $nama, public array $rows, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjek);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifikasi');
    }
}
