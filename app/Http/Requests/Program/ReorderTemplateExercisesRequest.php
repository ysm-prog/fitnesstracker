<?php

declare(strict_types=1);

namespace App\Http\Requests\Program;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reordering takes the complete sequence, not a pair to swap or a single item
 * to move. Partial input is how orderings drift out of sync between clients.
 */
final class ReorderTemplateExercisesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template_exercise_ids' => ['required', 'array', 'min:1', 'max:100'],
            'template_exercise_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var list<int> $submitted */
            $submitted = array_map('intval', (array) $this->input('template_exercise_ids', []));

            if (count($submitted) !== count(array_unique($submitted))) {
                $validator->errors()->add('template_exercise_ids', 'The same exercise was listed more than once.');

                return;
            }

            $program = $this->route('program');

            if ($program === null) {
                return;
            }

            $existing = $program->templateExercises()->pluck('id')->map('intval')->all();

            sort($submitted);
            sort($existing);

            if ($submitted !== $existing) {
                $validator->errors()->add(
                    'template_exercise_ids',
                    'The list must contain every exercise in this program exactly once.',
                );
            }
        });
    }
}
