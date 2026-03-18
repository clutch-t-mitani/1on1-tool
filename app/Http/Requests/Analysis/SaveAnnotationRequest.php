<?php

namespace App\Http\Requests\Analysis;

use Illuminate\Foundation\Http\FormRequest;

final class SaveAnnotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'annotation_text' => ['nullable', 'string', 'max:200'],
        ];
    }
}
