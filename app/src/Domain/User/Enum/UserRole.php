<?php

namespace App\Domain\User\Enum;

enum UserRole: string
{
    case ADMIN = 'admin';
    case AUTHOR = 'author';
    case READER = 'reader';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}