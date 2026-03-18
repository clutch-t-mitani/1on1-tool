<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'is_admin'   => ['boolean'],
        ];
    }
}
