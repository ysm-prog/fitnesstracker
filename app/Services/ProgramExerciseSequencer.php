<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TemplateExercise;
use App\Models\WorkoutTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Owns `position` for a program's exercises.
 *
 * Positions are dense and 1-based. Nothing else in the application writes the
 * column: it is not fillable, and every operation that could disturb the
 * sequence goes through here so the invariant is enforced in one place rather
 * than remembered in several.
 */
final class ProgramExerciseSequencer
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function append(WorkoutTemplate $program, array $attributes): TemplateExercise
    {
        return DB::transaction(function () use ($program, $attributes): TemplateExercise {
            // Lock the program row so two concurrent appends cannot read the
            // same "next" position and collide on the unique index.
            WorkoutTemplate::query()->whereKey($program->getKey())->lockForUpdate()->first();

            $nextPosition = (int) $program->templateExercises()->max('position') + 1;

            $templateExercise = $program->templateExercises()->make($attributes);
            $templateExercise->position = $nextPosition;
            $templateExercise->save();

            return $templateExercise;
        });
    }

    /**
     * Rewrite the whole sequence.
     *
     * Done in two passes. Assigning final positions directly would collide with
     * the unique index the moment two rows swap, so every row is first moved
     * out of the way into a range nothing occupies.
     *
     * @param  list<int>  $orderedIds  every exercise in the program, exactly once
     */
    public function reorder(WorkoutTemplate $program, array $orderedIds): void
    {
        DB::transaction(function () use ($program, $orderedIds): void {
            $offset = (int) $program->templateExercises()->max('position') + 1000;

            $program->templateExercises()
                ->getQuery()
                ->update(['position' => DB::raw("position + {$offset}")]);

            foreach ($orderedIds as $index => $id) {
                $program->templateExercises()
                    ->getQuery()
                    ->whereKey($id)
                    ->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Remove one prescription and close the gap it leaves.
     */
    public function remove(TemplateExercise $templateExercise): void
    {
        DB::transaction(function () use ($templateExercise): void {
            $program = $templateExercise->workoutTemplate;
            $removedPosition = $templateExercise->position;

            $templateExercise->delete();

            $program->templateExercises()
                ->getQuery()
                ->where('position', '>', $removedPosition)
                ->update(['position' => DB::raw('position - 1')]);
        });
    }
}
