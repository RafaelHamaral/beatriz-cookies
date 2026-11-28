<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Validation\ValidationException;

class Inventory
{
    /**
     * Units of each physical product consumed by the cart.
     * A loose product uses its own quantity. A combo uses quantity × registered units of each component.
     *
     * @param  list<array{product: Product, quantity: int}>  $lines
     * @return array<int, int>
     */
    public function demandFor(array $lines): array
    {
        $ids = array_map(fn (array $line): int => $line['product']->id, $lines);
        $components = ProductComponent::query()
            ->whereIn('combo_id', $ids)
            ->orderBy('id')
            ->get()
            ->groupBy('combo_id');

        $demand = [];
        foreach ($lines as $line) {
            $parts = $components->get($line['product']->id);
            if ($parts === null || $parts->isEmpty()) {
                $this->add($demand, $line['product']->id, $line['quantity']);
                continue;
            }

            foreach ($parts as $part) {
                $this->add($demand, (int) $part->product_id, $line['quantity'] * (int) $part->units);
            }
        }

        ksort($demand);

        return $demand;
    }

    /**
     * @param  list<array{product: Product, quantity: int}>  $lines
     */
    public function deduct(Order $order, array $lines): void
    {
        $demand = $this->demandFor($lines);
        if ($demand === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', array_keys($demand))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $shortages = [];
        foreach ($demand as $id => $units) {
            $product = $products->get($id);
            $available = $product->stock_units ?? 0;
            if ($product === null || $available < $units) {
                $name = $product->name ?? 'Produto';
                $shortages[] = "{$name} está indisponível: o pedido precisa de {$units} unidades e há {$available}.";
            }
        }

        if ($shortages !== []) {
            throw ValidationException::withMessages([
                'cart' => implode(' ', $shortages).' Ajuste o carrinho e tente de novo.',
            ]);
        }

        foreach ($demand as $id => $units) {
            if (! $this->take($id, $units)) {
                $name = $products->get($id)->name ?? 'Produto';
                throw ValidationException::withMessages([
                    'cart' => "{$name} está indisponível: o pedido precisa de {$units} unidades e o saldo acabou de mudar. Ajuste o carrinho e tente de novo.",
                ]);
            }

            $order->stockMovements()->create([
                'product_id' => $id,
                'units' => $units,
            ]);
        }
    }

    public function restore(Order $order): void
    {
        if ($order->stock_restored_at !== null) {
            return;
        }

        $movements = $order->stockMovements()->orderBy('product_id')->get();
        $products = Product::query()
            ->whereIn('id', $movements->pluck('product_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($movements as $movement) {
            $product = $products->get($movement->product_id);
            if ($product === null) {
                throw ValidationException::withMessages([
                    'status' => 'Não foi possível devolver o estoque porque um produto do pedido não existe mais.',
                ]);
            }

            $product->increment('stock_units', $movement->units);
        }

        $order->stock_restored_at = now();
    }

    public function take(int $productId, int $units): bool
    {
        return Product::query()
            ->whereKey($productId)
            ->where('stock_units', '>=', $units)
            ->decrement('stock_units', $units) === 1;
    }

    /**
     * @param  array<int, int>  $demand
     */
    private function add(array &$demand, int $productId, int $units): void
    {
        $next = ($demand[$productId] ?? 0) + $units;
        if ($units < 1 || $next > 100000) {
            throw ValidationException::withMessages([
                'cart' => 'A quantidade deste pedido passa do limite de estoque.',
            ]);
        }

        $demand[$productId] = $next;
    }
}
