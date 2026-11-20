<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\OrderUpdateRequest;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);
        $filters = $request->validate([
            'fulfillment' => ['nullable', Rule::in(array_keys(Order::FULFILLMENT))],
            'payment' => ['nullable', Rule::in(array_keys(Order::PAYMENT))],
            'number' => ['nullable', 'string', 'max:36'],
        ]);
        $orders = Order::query()->when($filters['fulfillment'] ?? null, fn ($q, $v) => $q->where('fulfillment_status', $v))
            ->when($filters['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['number'] ?? null, fn ($q, $v) => $q->where('number', $v))
            ->latest('id')->paginate(20)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        return view('admin.orders.show', ['order' => $order->load('items', 'paidBy')]);
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->version !== (int) $request->validated('version')) {
                throw ValidationException::withMessages(['status' => 'Este pedido foi alterado por outro operador. Recarregue a página.']);
            }
            $status = $request->validated('status');
            if ($request->routeIs('orders.payment')) {
                $allowed = $status === 'paid'
                    ? $locked->payment_status === 'pending' && $locked->fulfillment_status !== 'cancelled'
                    : $locked->payment_status === 'paid';
                if (! $allowed) {
                    throw ValidationException::withMessages(['status' => 'Esta alteração de pagamento não é permitida.']);
                }
                $from = $locked->payment_status;
                $locked->payment_status = $status;
                if ($status === 'paid') {
                    $locked->paid_at = now();
                    $locked->paid_by = $request->user()->id;
                }
                $action = 'order.payment_changed';
            } else {
                $transitions = [
                    'pending' => ['confirmed', 'cancelled'], 'confirmed' => ['preparing', 'cancelled'],
                    'preparing' => ['ready', 'cancelled'], 'ready' => ['completed', 'cancelled'],
                    'completed' => [], 'cancelled' => [],
                ];
                if (! in_array($status, $transitions[$locked->fulfillment_status], true) || ($status === 'completed' && $locked->payment_status !== 'paid')) {
                    throw ValidationException::withMessages(['status' => 'Etapa inválida. Siga a sequência de atendimento e confirme o pagamento antes de concluir.']);
                }
                $from = $locked->fulfillment_status;
                $locked->fulfillment_status = $status;
                $action = 'order.fulfillment_changed';
            }
            $locked->version++;
            $locked->save();
            AuditLogger::record($action, $locked, ['from' => $from, 'to' => $status]);
        }, 3);
        return redirect()->route('orders.show', $order)->with('success', 'Pedido atualizado.');
    }
}
