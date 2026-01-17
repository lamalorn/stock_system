<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:160','unique:users,email'],
            'phone' => ['nullable','string','max:30'],
            'password' => ['required','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'roles' => ['nullable','array'],
            'roles.*' => ['string'],
        ];
    }
}
