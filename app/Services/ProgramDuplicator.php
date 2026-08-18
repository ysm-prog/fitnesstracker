<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WorkoutTemplate;
use Illuminate\Support\Facades\DB;

final class ProgramDuplicator
{
    /**
     * Copy a program and its prescriptions under a new name.
     *
     * The copy is a new program from the moment it exists: it shares no rows
     * with the original, so editing either one leaves the other alone. The
     * duplicate starts inactive, because two active copies of the same program
     * is almost never what someone means by "duplicate".
     */
    public function duplicate(WorkoutTemplate $program, string $name): WorkoutTemplate
    {
        return DB::transaction(function () use ($program, $name): WorkoutTemplate {
            $copy = $program->user->workoutTemplates()->create([
                'name' => $name,
                'description' => $program->description,
                'is_active' => false,
            ]);

            foreach ($program->templateExercises()->get() as $templateExercise) {
                $copiedExercise = $copy->templateExercises()->make([
                    'exercise_id' => $templateExercise->exercise_id,
                    'target_sets' => $templateExercise->target_sets,
                    'min_reps' => $templateExercise->min_reps,
                    'max_reps' => $templateExercise->max_reps,
                    'target_rir' => $templateExercise->target_rir,
                    'rest_seconds' => $templateExercise->rest_seconds,
                    'is_optional' => $templateExercise->is_optional,
                    'notes' => $templateExercise->notes,
                ]);
                $copiedExercise->position = $templateExercise->position;
                $copiedExercise->save();
            }

            return $copy->fresh();
        });
    }
}
