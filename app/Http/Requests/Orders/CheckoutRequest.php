<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'checkout_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+() .-]{8,30}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
