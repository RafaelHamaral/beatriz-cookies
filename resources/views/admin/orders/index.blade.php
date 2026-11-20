@extends('layouts.restricted')
@section('content')
<section class="panel">
    <h1>Pedidos</h1>
    <form method="GET" action="{{ route('orders.index') }}">
        <label for="number">Número completo do pedido</label>
        <input id="number" name="number" maxlength="36" value="{{ request('number') }}">
        <label for="fulfillment">Atendimento</label>
        <select id="fulfillment" name="fulfillment">
            <option value="">Todos</option>
            @foreach (\App\Models\Order::FULFILLMENT as $value => $label)
                <option value="{{ $value }}" @selected(request('fulfillment') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="payment">Pagamento</label>
        <select id="payment" name="payment">
            <option value="">Todos</option>
            @foreach (\App\Models\Order::PAYMENT as $value => $label)
                <option value="{{ $value }}" @selected(request('payment') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p><button type="submit">Filtrar</button> <a href="{{ route('orders.index') }}">Limpar filtros</a></p>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Pedido / data</th><th>Cliente</th><th>Total</th><th>Atendimento</th><th>Pagamento</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a href="{{ route('orders.show', $order) }}">{{ $order->number }}</a><br>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ \App\Support\Money::format($order->total_cents) }}</td>
                        <td>{{ \App\Models\Order::FULFILLMENT[$order->fulfillment_status] }}</td>
                        <td>{{ \App\Models\Order::PAYMENT[$order->payment_status] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Nenhum pedido encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</section>
@endsection
