<?php

namespace App\Mail;

use App\Models\EventCost;
use App\Models\Member;
use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestSubmittedToHrmMail extends Mailable
{
    use Queueable, SerializesModels;

    public Request $requestModel;
    public Member $recipient;
    public EventCost $eventCost;

    public function __construct(Request $requestModel, Member $recipient, EventCost $eventCost)
    {
        $this->requestModel = $requestModel;
        $this->recipient = $recipient;
        $this->eventCost = $eventCost;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe Lief en Leed aanvraag'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.requestSubmittedToHrm',
            with: [
                'requestModel' => $this->requestModel,
                'recipient' => $this->recipient,
                'eventCost' => $this->eventCost,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
