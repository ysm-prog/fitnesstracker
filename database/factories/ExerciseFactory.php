<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Equipment;
use App\Enums\LoadingType;
use App\Enums\MuscleGroup;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
final class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Barbell Back Squat '.$this->faker->unique()->numberBetween(1, 100000),
            'primary_muscle' => MuscleGroup::Quads,
            'secondary_muscles' => [MuscleGroup::Glutes->value, MuscleGroup::Hamstrings->value],
            'equipment' => Equipment::Barbell,
            'instructions' => 'Brace, descend under control, drive up through mid-foot.',
            'loading_type' => LoadingType::ExternalWeight,
            'default_weight_increment_kg' => 2.5,
            'is_unilateral' => false,
            'is_bodyweight' => false,
            'default_rest_seconds' => 180,
        ];
    }

    /** A shared, ownerless exercise: readable by everyone, writable by nobody. */
    public function system(): self
    {
        return $this->state(fn (): array => ['user_id' => null]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }

    public function bodyweight(): self
    {
        return $this->state(fn (): array => [
            'name' => 'Pull-Up '.$this->faker->unique()->numberBetween(1, 100000),
            'primary_muscle' => MuscleGroup::Lats,
            'equipment' => Equipment::Bodyweight,
            'loading_type' => LoadingType::Bodyweight,
            'is_bodyweight' => true,
        ]);
    }

    public function assisted(): self
    {
        return $this->state(fn (): array => [
            'name' => 'Assisted Pull-Up '.$this->faker->unique()->numberBetween(1, 100000),
            'primary_muscle' => MuscleGroup::Lats,
            'equipment' => Equipment::Machine,
            'loading_type' => LoadingType::AssistedBodyweight,
            'is_bodyweight' => true,
        ]);
    }
}
