<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable','integer','exists:suppliers,id'],
            'purchase_no' => ['required','string','max:40',Rule::unique('purchases', 'purchase_no')],
            'currency_id' => ['required','integer','exists:currencies,id'],
            'status' => ['nullable','string'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','integer','exists:products,id'],
            'items.*.qty' => ['required','numeric','gt:0'],
            'items.*.cost' => ['required','numeric','min:0'],
        ];
    }
     public function messages(): array
    {
        return [
            'purchase_no.unique' => 'Purchase number already exists.',
        ];
    }
}
