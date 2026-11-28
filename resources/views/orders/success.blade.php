@extends('layouts.public')
@section('head')
    @if ($openWhatsApp)
        <meta http-equiv="refresh" content="2;url={{ $whatsappUrl }}">
    @endif
@endsection
@section('content')
<section class="panel">
    <h1>Pedido recebido</h1>
    <p>Número do pedido: <strong class="secret">{{ $order->number }}</strong></p>
    <p>Seu pedido foi registrado e aguarda confirmação da loja. O pagamento será combinado e registrado manualmente pela equipe.</p>
    @if ($whatsappUrl)
        @if ($openWhatsApp)
            <p role="status">Abrindo o WhatsApp com os dados do seu pedido… Se a conversa não abrir, use o botão abaixo.</p>
        @endif
        <p>A mensagem será enviada à loja somente quando você revisar e tocar em “Enviar” no WhatsApp. Você pode reabrir a conversa abaixo sem criar outro pedido.</p>
        <p><a class="button" href="{{ $whatsappUrl }}" rel="noreferrer">Abrir WhatsApp</a></p>
    @else
        <p>O WhatsApp da loja está indisponível no momento. Seu pedido está salvo e a loja poderá entrar em contato pelos dados informados.</p>
    @endif
    <p>Atendimento: {{ \App\Models\Order::FULFILLMENT[$order->fulfillment_status] }}</p>
    <p>Pagamento: {{ \App\Models\Order::PAYMENT[$order->payment_status] }}</p>
    <p>Total dos produtos: <strong>{{ \App\Support\Money::format($order->total_cents) }}</strong></p>
    <a href="{{ route('catalog.index') }}">Voltar ao catálogo</a>
</section>
@endsection
