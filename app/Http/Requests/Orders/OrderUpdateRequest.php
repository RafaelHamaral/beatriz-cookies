<?php

namespace App\Http\Requests\Orders;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('orders.payment') ? 'payment' : 'update', $this->route('order'));
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in($this->routeIs('orders.payment') ? ['paid', 'refunded'] : array_keys(Order::FULFILLMENT))],
        ];
    }
}
