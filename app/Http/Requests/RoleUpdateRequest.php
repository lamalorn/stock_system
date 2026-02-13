<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route model binding: PUT /roles/{role}
        $roleId = $this->route('role')->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // ignore current role id + still unique within guard api
                Rule::unique('roles', 'name')
                    ->where(fn ($q) => $q->where('guard_name', 'api'))
                    ->ignore($roleId),
            ],
        ];
    }
}
