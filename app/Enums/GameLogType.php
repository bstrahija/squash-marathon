<?php

namespace App\Enums;

enum GameLogType: string
{
    case Score = 'score';
    case Let = 'let';
    case Stroke = 'stroke';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases()
        );
    }
}
