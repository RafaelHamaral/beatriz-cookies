<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('review'));
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }
}
