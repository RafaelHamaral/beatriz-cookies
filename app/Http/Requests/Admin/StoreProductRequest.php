<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesStock;
use App\Models\Product;
use App\Rules\ProductImage as ProductImageRule;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use ValidatesStock;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'ingredients' => ['required', 'string', 'max:2000'],
            'allergens' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'string', 'max:12'],
            'is_available' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'cover' => ['nullable', 'string', 'max:40'],
            'images' => ['required', 'array', 'min:1', 'max:'.(int) config('catalog.max_images')],
            'images.*' => ['required', new ProductImageRule],
            ...$this->stockRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Escolha a categoria.',
            'category_id.exists' => 'Escolha uma categoria cadastrada.',
            'name.required' => 'Informe o nome do produto.',
            'description.required' => 'Informe a descrição.',
            'ingredients.required' => 'Informe os ingredientes.',
            'images.required' => 'Envie ao menos uma foto.',
            'images.min' => 'Envie ao menos uma foto.',
            'images.max' => 'Um produto pode ter no máximo '.(int) config('catalog.max_images').' fotos.',
            'stock_units.integer' => 'Informe o estoque em unidades inteiras.',
            'stock_units.min' => 'O estoque não pode ser negativo.',
            'stock_units.max' => 'O estoque passa do limite de 100.000 unidades.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cents = Money::parseToCents((string) $this->input('price', ''));
            if ($cents === null || $cents < 1) {
                $validator->errors()->add('price', 'Informe o preço com até duas casas, como 18,50.');
            }

            $files = $this->file('images', []);
            $count = is_array($files) ? count($files) : 0;
            $cover = (string) $this->input('cover', 'new:0');
            if ($cover === '') {
                $cover = 'new:0';
            }

            if (! preg_match('/^new:(\d+)$/', $cover, $matches) || (int) $matches[1] >= $count) {
                $validator->errors()->add('cover', 'Escolha a foto de capa.');
            }

            $this->validateStock($validator, null);
        });
    }

    public function priceCents(): int
    {
        return (int) Money::parseToCents((string) $this->input('price'));
    }
}
