<?php

namespace App\Http\Requests\DailyLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTextLogRequest extends FormRequest
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
            'question_id' => [
                'required',
                'integer',
                Rule::exists('questions', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'answer_text' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
