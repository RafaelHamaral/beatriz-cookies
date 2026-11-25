<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\CheckoutRequest;
use App\Models\Order;
use App\Services\Cart;
use App\Services\StoreNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart)
    {
        $lines = $cart->lines($request->session()->get('cart', []));
        if ($lines === []) {
            return redirect()->route('cart.index')->with('error', 'Adicione produtos antes de finalizar.');
        }
        $key = $request->session()->get('checkout_key', (string) Str::uuid());
        $request->session()->put('checkout_key', $key);
        $request->session()->put('checkout_quote', $this->quote($lines));
        return view('orders.checkout', ['lines' => $lines, 'checkoutKey' => $key, 'total' => array_sum(array_column($lines, 'total_cents'))]);
    }

    public function store(CheckoutRequest $request, Cart $cart)
    {
        $key = $request->validated('checkout_key');
        $receipts = $request->session()->get('order_receipts', []);
        if (isset($receipts[$key])) {
            return redirect()->route('checkout.success', $receipts[$key]);
        }
        abort_unless($key === $request->session()->get('checkout_key'), 419);
        $order = DB::transaction(function () use ($request, $cart, $key) {
            $existing = Order::where('checkout_key', $key)->first();
            if ($existing) { return $existing; }
            $lines = $cart->lines($request->session()->get('cart', []), true);
            if ($lines === []) {
                throw ValidationException::withMessages(['cart' => 'Seu carrinho está vazio.']);
            }
            if ($this->quote($lines) !== $request->session()->get('checkout_quote')) {
                throw ValidationException::withMessages(['cart' => 'Os preços mudaram. Reabra o checkout para revisar o total antes de confirmar.']);
            }
            $order = Order::create([
                ...$request->safe()->only(['customer_name', 'customer_email', 'customer_phone', 'notes']),
                'number' => (string) Str::uuid(), 'checkout_key' => $key,
                'total_cents' => array_sum(array_column($lines, 'total_cents')),
                'fulfillment_status' => 'pending', 'payment_status' => 'pending',
            ]);
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id, 'product_name' => $line['product']->name,
                    'unit_price_cents' => $line['product']->price_cents, 'quantity' => $line['quantity'], 'total_cents' => $line['total_cents'],
                ]);
            }
            StoreNotifications::record($order);
            return $order;
        }, 3);
        $receipts[$key] = $order->number;
        $request->session()->put('order_receipts', array_slice($receipts, -20, null, true));
        $request->session()->forget(['cart', 'checkout_key', 'checkout_quote']);
        return redirect()->route('checkout.success', $order->number);
    }

    public function success(Request $request, string $number)
    {
        abort_unless(in_array($number, $request->session()->get('order_receipts', []), true), 404);
        return view('orders.success', ['order' => Order::where('number', $number)->firstOrFail()]);
    }

    private function quote(array $lines): string
    {
        return hash('sha256', json_encode(array_map(fn ($line) => [$line['product']->id, $line['product']->price_cents, $line['quantity']], $lines)));
    }
}
