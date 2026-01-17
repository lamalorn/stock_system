<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_id' => ['required','integer','exists:sales,id'],
            'method_code' => ['required','string'], // CASH/BAKONG/KHQR
            'currency_id' => ['required','integer','exists:currencies,id'],
            'amount' => ['required','numeric','min:0'],
            'status' => ['nullable','string'],
        ];
    }
}
