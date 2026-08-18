<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TemplateExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `position` is deliberately not fillable. Order is changed only through the
 * reorder endpoint, which rewrites the whole sequence in one transaction;
 * letting a single update set its own position is how sequences acquire
 * duplicates and gaps.
 */
#[Fillable([
    'exercise_id',
    'target_sets',
    'min_reps',
    'max_reps',
    'target_rir',
    'rest_seconds',
    'is_optional',
    'notes',
])]
class TemplateExercise extends Model
{
    /** @use HasFactory<TemplateExerciseFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'target_sets' => 'integer',
            'min_reps' => 'integer',
            'max_reps' => 'integer',
            'target_rir' => 'integer',
            'rest_seconds' => 'integer',
            'is_optional' => 'boolean',
        ];
    }

    /** @return BelongsTo<WorkoutTemplate, $this> */
    public function workoutTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class);
    }

    /** @return BelongsTo<Exercise, $this> */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
