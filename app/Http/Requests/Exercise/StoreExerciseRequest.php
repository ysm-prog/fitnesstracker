<?php

declare(strict_types=1);

namespace App\Http\Requests\Exercise;

use App\Enums\Equipment;
use App\Enums\LoadingType;
use App\Enums\MuscleGroup;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                // Unique within this user's own library. A custom exercise may
                // share a name with a system one; that is a deliberate variant,
                // not a collision.
                Rule::unique('exercises', 'name')
                    ->where(fn (Builder $query) => $query->where('user_id', $this->user()->getKey())),
            ],
            'primary_muscle' => ['required', Rule::enum(MuscleGroup::class)],
            'secondary_muscles' => ['sometimes', 'nullable', 'array', 'max:8'],
            'secondary_muscles.*' => [Rule::enum(MuscleGroup::class)],
            'equipment' => ['required', Rule::enum(Equipment::class)],
            'instructions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'loading_type' => ['sometimes', Rule::enum(LoadingType::class)],
            'default_weight_increment_kg' => ['sometimes', 'numeric', 'gt:0', 'max:50'],
            'is_unilateral' => ['sometimes', 'boolean'],
            'is_bodyweight' => ['sometimes', 'boolean'],
            'default_rest_seconds' => ['sometimes', 'integer', 'between:0,900'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('user_id');
        $this->request->remove('archived_at');

        if (is_string($name = $this->input('name'))) {
            $this->merge(['name' => trim($name)]);
        }
    }

    /**
     * A movement cannot be marked as bodyweight while also being loaded with
     * external weight — the coach would then be told to add plates to a pull-up.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $loadingType = $this->input('loading_type', LoadingType::ExternalWeight->value);
            $isBodyweight = $this->boolean('is_bodyweight');

            if ($isBodyweight && $loadingType === LoadingType::ExternalWeight->value) {
                $validator->errors()->add(
                    'loading_type',
                    'A bodyweight exercise cannot use the external weight loading type.',
                );
            }

            $bodyweightTypes = [LoadingType::Bodyweight->value, LoadingType::AssistedBodyweight->value];

            if (! $isBodyweight && in_array($loadingType, $bodyweightTypes, true)) {
                $validator->errors()->add(
                    'is_bodyweight',
                    'A bodyweight or assisted loading type requires the bodyweight flag.',
                );
            }
        });
    }
}
