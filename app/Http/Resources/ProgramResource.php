<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkoutTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkoutTemplate
 */
final class ProgramResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_archived' => $this->isArchived(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'exercises' => TemplateExerciseResource::collection($this->whenLoaded('templateExercises')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
