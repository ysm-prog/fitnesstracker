<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Enums\MeasurementUnit;
use App\Enums\PrimaryGoal;
use App\Enums\TrainingLevel;
use App\Enums\WeightUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authoritative validation for the fitness profile.
 *
 * Ranges are deliberately generous rather than clinical: the job is to reject
 * data that is certainly wrong (a 4,000 kg bodyweight, a 3 cm height), not to
 * tell a user what their body is allowed to be.
 */
final class UpdateFitnessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'between:50,300'],
            'current_body_weight_kg' => ['sometimes', 'nullable', 'numeric', 'between:20,500'],
            'target_body_weight_kg' => ['sometimes', 'nullable', 'numeric', 'between:20,500'],

            'training_level' => ['sometimes', Rule::enum(TrainingLevel::class)],
            'primary_goal' => ['sometimes', Rule::enum(PrimaryGoal::class)],
            'weight_unit' => ['sometimes', Rule::enum(WeightUnit::class)],
            'measurement_unit' => ['sometimes', Rule::enum(MeasurementUnit::class)],

            'preferred_session_minutes' => ['sometimes', 'nullable', 'integer', 'between:10,240'],
            'training_days_per_week' => ['sometimes', 'nullable', 'integer', 'between:1,7'],
            'available_training_days' => ['sometimes', 'nullable', 'array', 'max:7'],
            'available_training_days.*' => [
                'string',
                Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            ],

            'dietary_preference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'training_limitations' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'height_cm.between' => 'Height must be between 50 and 300 cm.',
            'current_body_weight_kg.between' => 'Body weight must be between 20 and 500 kg.',
            'target_body_weight_kg.between' => 'Target body weight must be between 20 and 500 kg.',
            'preferred_session_minutes.between' => 'Session duration must be between 10 and 240 minutes.',
            'training_days_per_week.between' => 'Training days per week must be between 1 and 7.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // A client must never be able to reassign ownership, whichever spelling
        // it tries. Stripping here makes the intent explicit at the boundary;
        // the empty fillable list is the second line of defence.
        $this->request->remove('user_id');
        $this->request->remove('userId');
        $this->request->remove('id');

        if (is_array($days = $this->input('available_training_days'))) {
            $this->merge([
                'available_training_days' => array_values(array_unique(array_map(
                    static fn ($day) => is_string($day) ? mb_strtolower(trim($day)) : $day,
                    $days,
                ))),
            ]);
        }
    }
}
