<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exercise
 */
final class ExerciseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'primary_muscle' => $this->primary_muscle?->value,
            'secondary_muscles' => $this->secondary_muscles ?? [],
            'equipment' => $this->equipment?->value,
            'instructions' => $this->instructions,
            'loading_type' => $this->loading_type?->value,
            'default_weight_increment_kg' => $this->default_weight_increment_kg,
            'is_unilateral' => $this->is_unilateral,
            'is_bodyweight' => $this->is_bodyweight,
            'default_rest_seconds' => $this->default_rest_seconds,

            // `is_system` rather than the owner's identifier: a client needs to
            // know whether it may edit this, not who else exists.
            'is_system' => $this->isSystem(),
            'is_archived' => $this->isArchived(),
            'archived_at' => $this->archived_at?->toIso8601String(),
        ];
    }
}
