<?php

namespace App\Enums;

/**
 * How the reviews list is ordered (spec 007, FR-007).
 */
enum ReviewSort: string
{
    case Recent = 'recent';
    case Mine = 'mine';
    case Partner = 'partner';

    public function label(string $partnerName): string
    {
        return match ($this) {
            self::Recent => __('Most recent'),
            self::Mine => __('My highest rated'),
            self::Partner => __(":partner's highest rated", ['partner' => $partnerName]),
        };
    }
}
