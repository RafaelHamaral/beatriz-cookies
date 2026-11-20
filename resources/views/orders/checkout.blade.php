@extends('layouts.public')
@section('content')
<div class="product-page">
    <section class="panel">
        <h1>Finalizar pedido</h1>
        <p>Não é preciso criar uma conta. A loja entrará em contato para confirmar disponibilidade, combinar entrega ou retirada e orientar o pagamento. Nenhum pagamento é feito nesta tela.</p>
        <form method="POST" action="{{ route('checkout.store') }}">
            @csrf
            <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
            <label for="customer_name">Nome</label>
            <input id="customer_name" name="customer_name" autocomplete="name" maxlength="120" value="{{ old('customer_name') }}" required>
            <label for="customer_email">E-mail</label>
            <input id="customer_email" name="customer_email" type="email" autocomplete="email" maxlength="255" value="{{ old('customer_email') }}" required>
            <label for="customer_phone">Telefone / WhatsApp</label>
            <input id="customer_phone" name="customer_phone" type="tel" autocomplete="tel" maxlength="30" value="{{ old('customer_phone') }}" required>
            <label for="notes">Observações (opcional)</label>
            <textarea id="notes" name="notes" maxlength="2000">{{ old('notes') }}</textarea>
            <p><button type="submit">Enviar pedido · {{ \App\Support\Money::format($total) }}</button></p>
        </form>
        <a href="{{ route('cart.index') }}">Revisar carrinho</a>
    </section>
    <aside class="panel">
        <h2>Resumo do pedido</h2>
        @foreach ($lines as $line)
            <p>{{ $line['quantity'] }} × {{ $line['product']->name }} · {{ \App\Support\Money::format($line['total_cents']) }}</p>
        @endforeach
        <p><strong>Total: {{ \App\Support\Money::format($total) }}</strong></p>
        <p>Entrega ou retirada a combinar com a loja. Este total corresponde aos produtos.</p>
    </aside>
</div>
@endsection
