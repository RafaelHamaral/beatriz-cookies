<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesStock;
use App\Models\Product;
use App\Rules\ProductImage as ProductImageRule;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    use ValidatesStock;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && $this->user()?->can('update', $product) === true;
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
            'cover' => ['required', 'string', 'max:40'],
            'remove' => ['nullable', 'array'],
            'remove.*' => ['integer'],
            'images' => ['nullable', 'array', 'max:'.(int) config('catalog.max_images')],
            'images.*' => [new ProductImageRule],
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
            'name.required' => 'Informe o nome do produto.',
            'description.required' => 'Informe a descrição.',
            'ingredients.required' => 'Informe os ingredientes.',
            'cover.required' => 'Escolha a foto de capa.',
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

            $product = $this->route('product');
            if (! $product instanceof Product) {
                return;
            }

            $existing = $product->images()->pluck('id')->map(fn ($id) => (int) $id);
            $remove = collect($this->input('remove', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $existing->contains($id))
                ->values();
            $newFiles = $this->file('images', []);
            $newCount = is_array($newFiles) ? count($newFiles) : 0;
            $remaining = $existing->count() - $remove->count() + $newCount;
            $limit = (int) config('catalog.max_images');

            if ($remaining < 1) {
                $validator->errors()->add('images', 'O produto precisa manter ao menos uma foto.');
            }

            if ($remaining > $limit) {
                $validator->errors()->add('images', "Um produto pode ter no máximo {$limit} fotos.");
            }

            $cover = (string) $this->input('cover', '');
            if (preg_match('/^existing:(\d+)$/', $cover, $matches)) {
                $id = (int) $matches[1];
                if (! $existing->contains($id) || $remove->contains($id)) {
                    $validator->errors()->add('cover', 'Escolha uma foto de capa que continue no produto.');
                }
            } elseif (! preg_match('/^new:(\d+)$/', $cover, $matches) || (int) $matches[1] >= $newCount) {
                $validator->errors()->add('cover', 'Escolha uma foto de capa que continue no produto.');
            }

            $this->validateStock($validator, $product->id);
        });
    }

    public function priceCents(): int
    {
        return (int) Money::parseToCents((string) $this->input('price'));
    }
}
