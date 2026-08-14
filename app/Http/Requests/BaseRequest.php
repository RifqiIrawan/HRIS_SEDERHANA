<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared input hygiene. Authorization is handled by the `role:` middleware on
 * the routes, so requests only concern themselves with shape and content.
 */
abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Columns that must arrive as null rather than '' when the field is left
     * blank — nullable unique columns in particular, where two empty strings
     * would collide but two nulls would not.
     *
     * @return array<int, string>
     */
    protected function nullableFields(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $replacements = [];

        foreach ($this->nullableFields() as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) === '') {
                $replacements[$field] = null;
            }
        }

        if ($replacements !== []) {
            $this->merge($replacements);
        }
    }
}
