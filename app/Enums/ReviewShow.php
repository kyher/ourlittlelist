<?php

namespace App\Enums;

/**
 * Which items the reviews list shows, by who reviewed them (spec 007, FR-001).
 */
enum ReviewShow: string
{
    case All = 'all';
    case Mine = 'mine';
    case Partner = 'partner';
    case Waiting = 'waiting';

    public function label(string $partnerName): string
    {
        return match ($this) {
            self::All => __('All reviews'),
            self::Mine => __('Reviewed by me'),
            self::Partner => __('Reviewed by :partner', ['partner' => $partnerName]),
            self::Waiting => __('Waiting for my review'),
        };
    }
}
