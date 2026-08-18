<?php

declare(strict_types=1);

namespace App\Http\Requests\Program;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProgramRequest extends FormRequest
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
                'sometimes', 'string', 'max:255',
                Rule::unique('workout_templates', 'name')
                    ->ignore($this->route('program')?->getKey())
                    ->where(fn (Builder $query) => $query->where('user_id', $this->user()->getKey())),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
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
}
