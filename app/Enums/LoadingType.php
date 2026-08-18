<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How an exercise is loaded. This is the single most consequential field on an
 * exercise: it decides whether an estimated 1RM means anything, whether volume
 * can be computed, and — from Milestone 9 — whether the coach may add weight,
 * add reps, or take assistance away.
 */
enum LoadingType: string
{
    case ExternalWeight = 'external_weight';
    case Bodyweight = 'bodyweight';
    case AssistedBodyweight = 'assisted_bodyweight';
    case Time = 'time';
    case Distance = 'distance';

    /** Only external load has a meaningful weight × reps volume and Epley E1RM. */
    public function supportsExternalLoad(): bool
    {
        return $this === self::ExternalWeight;
    }

    /**
     * Assisted movements progress by taking assistance away, never by adding
     * weight. The progression engine relies on this distinction.
     */
    public function progressesByReducingAssistance(): bool
    {
        return $this === self::AssistedBodyweight;
    }

    /** Bodyweight work progresses in repetitions; its rep range is never changed automatically. */
    public function progressesByRepetitions(): bool
    {
        return $this === self::Bodyweight;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
