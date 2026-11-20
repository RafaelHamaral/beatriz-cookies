<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\CartRequest;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $quantities = $request->session()->get('cart', []);
        // Keep unavailable items visible so customers can remove them.
        $products = Product::whereIn('id', array_keys($quantities))->with('category')->get()->keyBy('id');
        $lines = [];
        foreach ($quantities as $id => $quantity) {
            $product = $products->get($id);
            $lines[] = ['id' => $id, 'product' => $product, 'quantity' => $quantity, 'available' => $product && $product->isVisibleOnStore() && $product->is_available];
        }
        return view('orders.cart', ['lines' => $lines]);
    }

    public function update(CartRequest $request, int $product)
    {
        $cart = $request->session()->get('cart', []);
        $quantity = (int) $request->validated('quantity');
        if ($quantity === 0) {
            unset($cart[$product]);
        } else {
            $cart[$product] = $quantity;
            app(Cart::class)->lines($cart);
        }
        $request->session()->put('cart', $cart);
        $request->session()->forget('checkout_key');
        return redirect()->route('cart.index')->with('success', 'Carrinho atualizado.');
    }
}
