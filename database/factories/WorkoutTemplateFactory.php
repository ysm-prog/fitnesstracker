<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutTemplate>
 */
final class WorkoutTemplateFactory extends Factory
{
    protected $model = WorkoutTemplate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Upper A '.$this->faker->unique()->numberBetween(1, 100000),
            'description' => 'Upper body, strength emphasis.',
            'is_active' => true,
        ];
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['archived_at' => now(), 'is_active' => false]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
