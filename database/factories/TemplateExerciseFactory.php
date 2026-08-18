<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateExercise>
 */
final class TemplateExerciseFactory extends Factory
{
    protected $model = TemplateExercise::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workout_template_id' => WorkoutTemplate::factory(),
            'exercise_id' => Exercise::factory(),
            'target_sets' => 3,
            'min_reps' => 8,
            'max_reps' => 10,
            'target_rir' => 2,
            'rest_seconds' => 180,
            'is_optional' => false,
            'notes' => null,
        ];
    }

    /**
     * `position` is not fillable — the sequencer owns it in production — so the
     * factory assigns the next free position the same way, rather than letting
     * tests invent orderings the application could never produce.
     */
    public function configure(): self
    {
        return $this->afterMaking(function (TemplateExercise $templateExercise): void {
            if ($templateExercise->position !== null) {
                return;
            }

            $taken = TemplateExercise::query()
                ->where('workout_template_id', $templateExercise->workout_template_id)
                ->max('position');

            $templateExercise->position = (int) $taken + 1;
        });
    }

    public function optional(): self
    {
        return $this->state(fn (): array => ['is_optional' => true]);
    }
}
