@extends('layouts.public')

@section('content')
    <section class="hero">
        <div>
            <p class="eyebrow">Cookies artesanais e doces gourmet</p>
            <h1>Beatriz &amp; Kathy Cookies</h1>
            <p class="lede">Fornadas pequenas, massa amanteigada e doces feitos para encomenda. Escolha pelo catálogo e aguarde a confirmação da loja.</p>
            <p><a class="button" href="{{ route('catalog.index') }}">Ver o catálogo</a></p>
        </div>
        <aside class="hero-aside">
            <span>Do forno</span>
            <strong>Chocolate, creme e o que estiver na vitrine.</strong>
            <span>Fotos, ingredientes e alergênicos em cada produto.</span>
        </aside>
    </section>

    <section>
        <h2>Saindo do forno</h2>
        @if ($products->isEmpty())
            <p>A vitrine ainda não tem produtos ativos.</p>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    @include('catalog._card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>

    @if ($reviews->isNotEmpty())
        <section class="reviews-section">
            <p class="eyebrow">Quem provou, contou</p>
            <h2>Avaliações dos clientes</h2>
            <div class="review-grid">
                @foreach ($reviews as $review)
                    <blockquote class="review-card">
                        <p class="review-stars" aria-label="{{ $review->rating }} de 5 estrelas">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                        <p>“{{ $review->comment }}”</p>
                        <footer>{{ $review->name }}</footer>
                    </blockquote>
                @endforeach
            </div>
        </section>
    @endif
@endsection
