<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_no' => ['required','string','max:40'],
            'customer_id' => ['nullable','integer','exists:customers,id'],
            'status' => ['nullable','string'],
            'sale_type' => ['nullable','string'], // RETAIL/PACKAGE
            'currency_id' => ['required','integer','exists:currencies,id'],
            'discount' => ['nullable','numeric','min:0'],
            'paid_amount' => ['nullable','numeric','min:0'],
            'change_amount' => ['nullable','numeric','min:0'],

            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','integer','exists:products,id'],
            'items.*.qty' => ['required','numeric','gt:0'],
            'items.*.price' => ['nullable','numeric','min:0'],
            'items.*.discount' => ['nullable','numeric','min:0'],
        ];
    }
}
