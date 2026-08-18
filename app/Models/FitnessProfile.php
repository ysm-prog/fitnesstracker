<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MeasurementUnit;
use App\Enums\PrimaryGoal;
use App\Enums\TrainingLevel;
use App\Enums\WeightUnit;
use Database\Factories\FitnessProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note what is absent from the fillable list: `user_id`. A payload can never
 * reassign a profile to another user, however it is nested or spelled.
 */
#[Fillable([
    'height_cm',
    'current_body_weight_kg',
    'target_body_weight_kg',
    'training_level',
    'primary_goal',
    'weight_unit',
    'measurement_unit',
    'preferred_session_minutes',
    'training_days_per_week',
    'available_training_days',
    'dietary_preference',
    'training_limitations',
])]
class FitnessProfile extends Model
{
    /** @use HasFactory<FitnessProfileFactory> */
    use HasFactory;

    /**
     * Defaults live on the model as well as the column, so a row created this
     * request reads back with its defaults instead of nulls.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'training_level' => 'beginner',
        'primary_goal' => 'maintenance',
        'weight_unit' => 'kg',
        'measurement_unit' => 'cm',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height_cm' => 'float',
            'current_body_weight_kg' => 'float',
            'target_body_weight_kg' => 'float',
            'training_level' => TrainingLevel::class,
            'primary_goal' => PrimaryGoal::class,
            'weight_unit' => WeightUnit::class,
            'measurement_unit' => MeasurementUnit::class,
            'preferred_session_minutes' => 'integer',
            'training_days_per_week' => 'integer',
            'available_training_days' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
