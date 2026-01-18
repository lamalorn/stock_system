<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Later you can replace this with permission check
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],

            'name' => ['required', 'string', 'max:200'],

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku'),
            ],

            'cost' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],

            'currency_id' => ['required', 'exists:currencies,id'],

            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'min_qty' => ['nullable', 'integer', 'min:0'],

            'unit' => ['required', 'string', 'max:30'],

            'is_bundle' => ['nullable', 'boolean'],
            'is_recipe' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
