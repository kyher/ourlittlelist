<?php

namespace App\Enums;

enum CoupleRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    /**
     * Only pending requests can change. Every other status is final.
     */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
