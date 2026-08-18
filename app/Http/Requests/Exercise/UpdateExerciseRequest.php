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

final class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $exercise = $this->route('exercise');

        return [
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('exercises', 'name')
                    ->ignore($exercise?->getKey())
                    ->where(fn (Builder $query) => $query->where('user_id', $this->user()->getKey())),
            ],
            'primary_muscle' => ['sometimes', Rule::enum(MuscleGroup::class)],
            'secondary_muscles' => ['sometimes', 'nullable', 'array', 'max:8'],
            'secondary_muscles.*' => [Rule::enum(MuscleGroup::class)],
            'equipment' => ['sometimes', Rule::enum(Equipment::class)],
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
     * The loading rules apply to the resulting exercise, not just the fields in
     * this request, so a partial update cannot leave an incoherent combination.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $exercise = $this->route('exercise');

            if ($exercise === null) {
                return;
            }

            $loadingType = $this->input('loading_type', $exercise->loading_type->value);
            $isBodyweight = $this->has('is_bodyweight')
                ? $this->boolean('is_bodyweight')
                : $exercise->is_bodyweight;

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
