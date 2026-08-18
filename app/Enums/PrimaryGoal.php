<?php

declare(strict_types=1);

namespace App\Enums;

enum PrimaryGoal: string
{
    case LeanMuscleGain = 'lean_muscle_gain';
    case MuscleGain = 'muscle_gain';
    case Strength = 'strength';
    case FatLoss = 'fat_loss';
    case Maintenance = 'maintenance';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
