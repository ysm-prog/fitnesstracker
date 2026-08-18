<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FitnessProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FitnessProfile
 */
final class FitnessProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'height_cm' => $this->height_cm,
            'current_body_weight_kg' => $this->current_body_weight_kg,
            'target_body_weight_kg' => $this->target_body_weight_kg,
            'training_level' => $this->training_level?->value,
            'primary_goal' => $this->primary_goal?->value,
            'weight_unit' => $this->weight_unit?->value,
            'measurement_unit' => $this->measurement_unit?->value,
            'preferred_session_minutes' => $this->preferred_session_minutes,
            'training_days_per_week' => $this->training_days_per_week,
            'available_training_days' => $this->available_training_days,
            'dietary_preference' => $this->dietary_preference,
            'training_limitations' => $this->training_limitations,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
