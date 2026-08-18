<?php

declare(strict_types=1);

namespace App\Enums;

enum Equipment: string
{
    case Barbell = 'barbell';
    case Dumbbell = 'dumbbell';
    case Machine = 'machine';
    case Cable = 'cable';
    case SmithMachine = 'smith_machine';
    case Kettlebell = 'kettlebell';
    case Band = 'band';
    case Bodyweight = 'bodyweight';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
