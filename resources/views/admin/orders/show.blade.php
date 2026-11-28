@extends('layouts.restricted')
@section('content')
<section class="panel">
    <p><a href="{{ route('orders.index') }}">Todos os pedidos</a></p>
    <h1>Pedido <span class="secret">{{ $order->number }}</span></h1>
    <p>{{ $order->customer_name }} · {{ $order->customer_email }} · {{ $order->customer_phone }}</p>
    <p>Recebido em {{ $order->created_at->format('d/m/Y H:i') }}</p>
    <p>Observações: {{ $order->notes ?: 'Nenhuma.' }}</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produto</th><th>Quantidade</th><th>Preço unitário</th><th>Subtotal</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td data-label="Produto">{{ $item->product_name }}</td>
                        <td data-label="Quantidade">{{ $item->quantity }}</td>
                        <td data-label="Preço unitário">{{ \App\Support\Money::format($item->unit_price_cents) }}</td>
                        <td data-label="Subtotal">{{ \App\Support\Money::format($item->total_cents) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p><strong>Total: {{ \App\Support\Money::format($order->total_cents) }}</strong></p>
    @if ($order->stockMovements->isNotEmpty())
        <h2>Unidades baixadas do estoque</h2>
        <ul>
            @foreach ($order->stockMovements as $movement)
                <li>{{ $movement->units }} × {{ $movement->product->name ?? 'Produto removido' }}</li>
            @endforeach
        </ul>
        @if ($order->stock_restored_at)
            <p>Estoque devolvido em {{ $order->stock_restored_at->format('d/m/Y H:i') }}.</p>
        @endif
    @endif
    <p>Atendimento: {{ \App\Models\Order::FULFILLMENT[$order->fulfillment_status] }}</p>
    <p>Pagamento: {{ \App\Models\Order::PAYMENT[$order->payment_status] }}</p>
    @if ($order->paid_at)
        <p>Pagamento confirmado em {{ $order->paid_at->format('d/m/Y H:i') }} por {{ $order->paidBy?->name ?? 'Operador removido' }}.</p>
    @endif
    @php
        $nextStatuses = match ($order->fulfillment_status) {
            'pending' => ['confirmed', 'cancelled'], 'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'], 'ready' => $order->payment_status === 'paid' ? ['completed', 'cancelled'] : ['cancelled'],
            default => [],
        };
    @endphp
    @if ($nextStatuses)
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="version" value="{{ $order->version }}">
            <label for="status">Próxima etapa do atendimento</label>
            <select name="status" id="status">
                @foreach ($nextStatuses as $value)
                    <option value="{{ $value }}">{{ \App\Models\Order::FULFILLMENT[$value] }}</option>
                @endforeach
            </select>
            <p><button type="submit">Atualizar atendimento</button></p>
        </form>
    @endif
    @if ($order->fulfillment_status === 'cancelled' && $order->payment_status === 'paid')
        <p class="alert">Pedido cancelado com pagamento registrado. O administrador deve registrar o estorno depois de devolver o valor ao cliente.</p>
    @endif
    @can('payment', $order)
        @if (($order->payment_status === 'pending' && $order->fulfillment_status !== 'cancelled') || $order->payment_status === 'paid')
            <form method="POST" action="{{ route('orders.payment', $order) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="version" value="{{ $order->version }}">
                <input type="hidden" name="status" value="{{ $order->payment_status === 'paid' ? 'refunded' : 'paid' }}">
                <p class="hint">Registre somente após verificar o recebimento ou a devolução do valor. Esta ação não movimenta dinheiro.</p>
                <button type="submit">{{ $order->payment_status === 'paid' ? 'Registrar estorno realizado' : 'Confirmar pagamento recebido' }}</button>
            </form>
        @endif
    @endcan
</section>
@endsection
