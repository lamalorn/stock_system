<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['sometimes','string','max:120'],
            'email' => ['sometimes','email','max:160','unique:users,email,'.$userId],
            'phone' => ['nullable','string','max:30'],
            'password' => ['nullable','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'roles' => ['nullable','array'],
            'roles.*' => ['string'],
        ];
    }
}
