<?php

declare(strict_types=1);

namespace App\Rules;

use App\Exceptions\InvalidProductImage;
use App\Services\ProductImageStorage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ProductImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Não foi possível receber a imagem.');

            return;
        }

        try {
            app(ProductImageStorage::class)->inspect($value);
        } catch (InvalidProductImage $exception) {
            $fail($exception->getMessage());
        }
    }
}
