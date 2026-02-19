<?php

namespace App\Enums;

enum RoleName: string
{
    case Player = 'player';
    case Admin = 'admin';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Player->value => 'Player',
            self::Admin->value => 'Admin',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases()
        );
    }
}
