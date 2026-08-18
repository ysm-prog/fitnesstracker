<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementUnit: string
{
    case Centimetres = 'cm';
    case Inches = 'in';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
