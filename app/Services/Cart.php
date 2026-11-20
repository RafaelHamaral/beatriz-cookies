<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class Cart
{
    public function lines(array $quantities, bool $lock = false): array
    {
        if (count($quantities) > 50) {
            throw ValidationException::withMessages(['cart' => 'O carrinho aceita até 50 produtos diferentes.']);
        }
        $query = Product::query()->whereIn('id', array_keys($quantities))->orderBy('id');
        $products = ($lock ? $query->lockForUpdate() : $query)->get();
        // Lock categories too, so deactivation cannot race checkout.
        $categoryQuery = \App\Models\Category::whereIn('id', $products->pluck('category_id')->unique())->orderBy('id');
        $categories = ($lock ? $categoryQuery->lockForUpdate() : $categoryQuery)->get()->keyBy('id');
        $lines = [];
        foreach ($quantities as $id => $quantity) {
            $product = $products->firstWhere('id', $id);
            if (! $product || ! $product->is_active || ! $product->is_available || ! $categories->get($product->category_id)?->is_active || ! is_int($quantity) || $quantity < 1 || $quantity > 99) {
                throw ValidationException::withMessages(['cart' => 'Um produto do carrinho está indisponível. Remova-o para continuar.']);
            }
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'total_cents' => $product->price_cents * $quantity];
        }
        return $lines;
    }
}
