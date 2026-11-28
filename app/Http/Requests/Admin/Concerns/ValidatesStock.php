<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Concerns;

use App\Support\ComboComposition;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesStock
{
    /**
     * @return array<string, mixed>
     */
    protected function stockRules(): array
    {
        return [
            'stock_units' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'components' => ['nullable', 'array', 'max:12'],
            'components.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'components.*.units' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function validateStock(Validator $validator, ?int $productId): void
    {
        $this->attributes->set(
            'combo_rows',
            ComboComposition::parse($this->input('components', []), $validator, $productId),
        );
    }

    /**
     * @return list<array{product_id: int, units: int}>
     */
    public function comboRows(): array
    {
        return $this->attributes->get('combo_rows', []);
    }

    public function stockUnits(): int
    {
        return (int) $this->input('stock_units', 0);
    }
}
