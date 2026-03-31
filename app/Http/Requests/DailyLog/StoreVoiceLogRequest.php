<?php

namespace App\Http\Requests\DailyLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVoiceLogRequest extends FormRequest
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
            'audio' => ['required', 'file', 'mimetypes:audio/webm,audio/mp4,audio/wav', 'max:25600'],
        ];
    }
}
