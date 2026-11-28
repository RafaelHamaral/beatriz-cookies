<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Contracts\Validation\Validator;

class ComboComposition
{
    /**
     * @param  mixed  $input
     * @return list<array{product_id: int, units: int}>
     */
    public static function parse(mixed $input, Validator $validator, ?int $productId): array
    {
        $rows = [];
        $seen = [];

        foreach (array_values(is_array($input) ? $input : []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = (int) ($row['product_id'] ?? 0);
            $unitsRaw = $row['units'] ?? null;
            if ($id === 0 && ($unitsRaw === null || $unitsRaw === '')) {
                continue;
            }

            if ($id === 0) {
                $validator->errors()->add("components.{$index}.product_id", 'Escolha o produto desta linha do combo.');
                continue;
            }

            $units = (int) $unitsRaw;
            if ($units < 1 || $units > 99) {
                $validator->errors()->add("components.{$index}.units", 'Informe de 1 a 99 unidades por produto do combo.');
            }

            if ($productId !== null && $id === $productId) {
                $validator->errors()->add("components.{$index}.product_id", 'Um combo não pode incluir o próprio produto.');
            }

            if (isset($seen[$id])) {
                $validator->errors()->add("components.{$index}.product_id", 'Cada produto entra uma única vez no combo.');
            }

            $seen[$id] = true;
            $rows[] = ['product_id' => $id, 'units' => $units];
        }

        if ($rows !== [] && $productId !== null && ProductComponent::query()->where('product_id', $productId)->exists()) {
            $validator->errors()->add('components', 'Este produto já entra em outro combo e não pode virar um combo.');
        }

        $ids = array_column($rows, 'product_id');
        if ($ids !== [] && Product::query()->whereIn('id', $ids)->whereHas('components')->exists()) {
            $validator->errors()->add('components', 'Escolha produtos avulsos. Um combo não pode conter outro combo.');
        }

        return $rows;
    }
}
