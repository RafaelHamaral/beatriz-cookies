<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Rules\ProductImage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->managesCatalog() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', new ProductImage],
            'images' => ['nullable', 'array', 'max:7'],
            'images.*' => ['required', new ProductImage],
            'remove_images' => ['sometimes', 'array', 'max:8'],
            'remove_images.*' => ['string', 'max:50'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
