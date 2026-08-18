<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TemplateExercise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TemplateExercise
 */
final class TemplateExerciseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'prescription' => [
                'target_sets' => $this->target_sets,
                'min_reps' => $this->min_reps,
                'max_reps' => $this->max_reps,
                'target_rir' => $this->target_rir,
                'rest_seconds' => $this->rest_seconds,
            ],
            'is_optional' => $this->is_optional,
            'notes' => $this->notes,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
        ];
    }
}
