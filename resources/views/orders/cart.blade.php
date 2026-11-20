@extends('layouts.public')
@section('content')
<section class="panel">
    <h1>Seu carrinho</h1>
    @forelse ($lines as $line)
        <article class="card">
            <h2>{{ $line['product']?->name ?? 'Produto removido' }}</h2>
            @if ($line['available'])
                <p>{{ \App\Support\Money::format($line['product']->price_cents) }} por unidade · Subtotal {{ \App\Support\Money::format($line['product']->price_cents * $line['quantity']) }}</p>
            @else
                <p class="alert">Produto indisponível. Remova-o para continuar.</p>
            @endif
            <form method="POST" action="{{ route('cart.update', $line['id']) }}">
                @csrf @method('PUT')
                <label for="quantity-{{ $line['id'] }}">Quantidade (use zero para remover)</label>
                <input id="quantity-{{ $line['id'] }}" type="number" name="quantity" min="0" max="99" value="{{ $line['quantity'] }}" required>
                <p><button type="submit">Atualizar quantidade</button></p>
            </form>
        </article>
    @empty
        <p>Seu carrinho está vazio.</p>
    @endforelse
    <p><a href="{{ route('catalog.index') }}">Continuar escolhendo</a></p>
    @if (count($lines) && collect($lines)->every(fn ($line) => $line['available']))
        <p><strong>Total: {{ \App\Support\Money::format(collect($lines)->sum(fn ($line) => $line['product']->price_cents * $line['quantity'])) }}</strong></p>
        <a class="button" href="{{ route('checkout.create') }}">Finalizar pedido</a>
    @endif
</section>
@endsection
