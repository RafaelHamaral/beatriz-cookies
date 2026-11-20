@extends('layouts.public')
@section('content')
<section class="panel">
    <h1>Pedido recebido</h1>
    <p>Número do pedido: <strong class="secret">{{ $order->number }}</strong></p>
    <p>Seu pedido foi enviado e aguarda confirmação da loja. O pagamento será combinado e registrado manualmente pela equipe.</p>
    <p>Atendimento: {{ \App\Models\Order::FULFILLMENT[$order->fulfillment_status] }}</p>
    <p>Pagamento: {{ \App\Models\Order::PAYMENT[$order->payment_status] }}</p>
    <p>Total dos produtos: <strong>{{ \App\Support\Money::format($order->total_cents) }}</strong></p>
    <a href="{{ route('catalog.index') }}">Voltar ao catálogo</a>
</section>
@endsection
