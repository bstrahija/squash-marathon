<?php

namespace App\Enums;

enum GameLogSide: string
{
    case Left = 'left';
    case Right = 'right';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $side): string => $side->value,
            self::cases()
        );
    }
}
