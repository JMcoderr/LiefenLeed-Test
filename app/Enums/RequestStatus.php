<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case EXPORTED = 'exported';

    public function label(): string {
        return match($this) {
            RequestStatus::PENDING => 'Pending',
            RequestStatus::ACCEPTED => 'Accepted',
            RequestStatus::REJECTED => 'Rejected',
            RequestStatus::PAID => 'Paid',
            RequestStatus::EXPORTED => 'Exported',
        };
    }
}
