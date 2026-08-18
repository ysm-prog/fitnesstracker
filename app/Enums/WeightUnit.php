<?php

declare(strict_types=1);

namespace App\Enums;

enum WeightUnit: string
{
    case Kilograms = 'kg';
    case Pounds = 'lb';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
