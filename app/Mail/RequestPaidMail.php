<?php

namespace App\Mail;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public Request $requestModel;
    private string $iban;
    private string $account_name;

    public function __construct(Request $requestModel, string $iban, string $account_name)
    {
        $this->requestModel = $requestModel;
        $this->iban = $iban;
        $this->account_name = $account_name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💸 Je aanvraag is uitbetaald'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.requestPaid',
            with: [
                'requestModel' => $this->requestModel,
                'iban' => $this->iban,
                'account_name' => $this->account_name,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}


