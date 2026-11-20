<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('message'));
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['handled', 'open', 'unread'])],
        ];
    }
}
