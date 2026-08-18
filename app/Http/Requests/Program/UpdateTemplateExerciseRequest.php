<?php

declare(strict_types=1);

namespace App\Http\Requests\Program;

use App\Models\Exercise;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTemplateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Changing the exercise in place is the "replace" operation: the
            // prescription stays, the movement changes.
            'exercise_id' => ['sometimes', 'integer', Rule::exists('exercises', 'id')],
            'target_sets' => ['sometimes', 'integer', 'between:1,20'],
            'min_reps' => ['sometimes', 'integer', 'between:1,100'],
            'max_reps' => ['sometimes', 'integer', 'between:1,100'],
            'target_rir' => ['sometimes', 'nullable', 'integer', 'between:0,5'],
            'rest_seconds' => ['sometimes', 'integer', 'between:0,900'],
            'is_optional' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('position');
        $this->request->remove('workout_template_id');
    }

    /**
     * The rep range is checked against the resulting row, not the payload, so
     * sending only `min_reps` cannot leave a minimum above the stored maximum.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $templateExercise = $this->route('templateExercise');

            if ($templateExercise === null) {
                return;
            }

            $minReps = (int) $this->input('min_reps', $templateExercise->min_reps);
            $maxReps = (int) $this->input('max_reps', $templateExercise->max_reps);

            if ($minReps > $maxReps) {
                $validator->errors()->add('min_reps', 'The minimum repetitions must not exceed the maximum.');
            }

            if ($this->has('exercise_id')) {
                $exercise = Exercise::visibleTo($this->user())->active()->find((int) $this->input('exercise_id'));

                if ($exercise === null) {
                    $validator->errors()->add('exercise_id', 'That exercise is not available.');
                }
            }
        });
    }
}
