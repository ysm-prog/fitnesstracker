<?php

declare(strict_types=1);

namespace App\Http\Requests\Program;

use App\Models\Exercise;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The prescription ranges here are the ones the brief pins down, and they are
 * enforced in three places: this request, a check constraint on PostgreSQL, and
 * the tests that assert both.
 */
final class StoreTemplateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', Rule::exists('exercises', 'id')],
            'target_sets' => ['required', 'integer', 'between:1,20'],
            'min_reps' => ['required', 'integer', 'between:1,100', 'lte:max_reps'],
            'max_reps' => ['required', 'integer', 'between:1,100'],
            'target_rir' => ['sometimes', 'nullable', 'integer', 'between:0,5'],
            'rest_seconds' => ['required', 'integer', 'between:0,900'],
            'is_optional' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'min_reps.lte' => 'The minimum repetitions must not exceed the maximum.',
            'target_sets.between' => 'Target sets must be between 1 and 20.',
            'rest_seconds.between' => 'Rest must be between 0 and 900 seconds.',
            'target_rir.between' => 'Target RIR must be between 0 and 5.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('position');
        $this->request->remove('workout_template_id');
    }

    /**
     * An exercise must be one this user can actually see, and not archived.
     * `exists` alone would let a caller prescribe another user's private
     * exercise by guessing its identifier.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $exerciseId = $this->input('exercise_id');

            if (! is_numeric($exerciseId)) {
                return;
            }

            $exercise = Exercise::visibleTo($this->user())->active()->find((int) $exerciseId);

            if ($exercise === null) {
                $validator->errors()->add('exercise_id', 'That exercise is not available.');
            }
        });
    }
}
