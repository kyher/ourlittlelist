<?php

namespace App\Enums;

enum ReviewCategory: string
{
    case Film = 'film';
    case TvShow = 'tv_show';
    case Book = 'book';
    case Food = 'food';
    case Restaurant = 'restaurant';

    public function label(): string
    {
        return match ($this) {
            self::Film => __('Film'),
            self::TvShow => __('TV show'),
            self::Book => __('Book'),
            self::Food => __('Food'),
            self::Restaurant => __('Restaurant'),
        };
    }
}
