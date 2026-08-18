<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MeasurementUnit;
use App\Enums\PrimaryGoal;
use App\Enums\TrainingLevel;
use App\Enums\WeightUnit;
use App\Models\FitnessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FitnessProfile>
 */
final class FitnessProfileFactory extends Factory
{
    protected $model = FitnessProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'height_cm' => 178.0,
            'current_body_weight_kg' => 82.5,
            'target_body_weight_kg' => 86.0,
            'training_level' => TrainingLevel::Intermediate,
            'primary_goal' => PrimaryGoal::LeanMuscleGain,
            'weight_unit' => WeightUnit::Kilograms,
            'measurement_unit' => MeasurementUnit::Centimetres,
            'preferred_session_minutes' => 60,
            'training_days_per_week' => 4,
            'available_training_days' => ['monday', 'tuesday', 'thursday', 'friday'],
            'dietary_preference' => null,
            'training_limitations' => null,
        ];
    }
}
