<?php

namespace App\Http\Requests\Analysis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublishAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'viewer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
