<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu producto ya esta disponible otra vez',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.back-in-stock',
        );
    }
}
