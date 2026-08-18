<?php

declare(strict_types=1);

namespace App\Enums;

enum MuscleGroup: string
{
    case Chest = 'chest';
    case Back = 'back';
    case Lats = 'lats';
    case Traps = 'traps';
    case Shoulders = 'shoulders';
    case Biceps = 'biceps';
    case Triceps = 'triceps';
    case Forearms = 'forearms';
    case Core = 'core';
    case Quads = 'quads';
    case Hamstrings = 'hamstrings';
    case Glutes = 'glutes';
    case Calves = 'calves';
    case Adductors = 'adductors';
    case Abductors = 'abductors';
    case FullBody = 'full_body';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
