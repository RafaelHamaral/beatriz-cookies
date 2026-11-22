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

    @if ($about->is_active)
        <section id="sobre" class="store-section about-section {{ $about->gallery() ? 'about-section--with-image' : '' }}" aria-labelledby="sobre-titulo">
            @if ($about->gallery())
                @php
                    $imageCount = count($about->gallery());
                    $imageInterval = 2; // Segundos entre as trocas de imagem.
                    $fadeDuration = 0.8; // Duração da transição, menor que o intervalo.
                    $carouselDuration = $imageCount * $imageInterval;
                @endphp
                <div class="about-media">
                    @if ($imageCount > 1)
                        <!--<input class="about-pause" id="about-pause" type="checkbox">
                        <label class="about-pause-label" for="about-pause">Pausar transição</label>-->
                        <style>
                            @keyframes about-step {
                                0% { opacity: 1; }
                                {{ 100 * $imageInterval / $carouselDuration }}%, 100% { opacity: 0; }
                            }
                            @keyframes about-fade {
                                0%, {{ 100 * ($imageInterval - $fadeDuration) / $carouselDuration }}% { opacity: 1; }
                                {{ 100 * $imageInterval / $carouselDuration }}%, {{ 100 * ($carouselDuration - $fadeDuration) / $carouselDuration }}% { opacity: 0; }
                                100% { opacity: 1; }
                            }
                        </style>
                    @endif
                    <div class="about-slides {{ $imageCount > 1 ? 'about-slides--animated' : '' }}" style="--duration: {{ $carouselDuration }}s">
                        @foreach ($about->gallery() as $key => $image)
                            <img class="about-image" src="{{ route('about.image', ['image' => $key]) }}" alt="{{ $about->image_alt ?: $about->title }}{{ $imageCount > 1 ? ' — imagem '.$loop->iteration.' de '.$imageCount : '' }}" width="800" height="600" style="--delay: {{ -(($imageCount - $loop->index) % $imageCount) * $imageInterval }}s">
                        @endforeach
                    </div>
                </div>
            @endif
            <div>
                <p class="eyebrow">Nossa história</p>
                <h2 id="sobre-titulo">{{ $about->title }}</h2>
                <p class="about-text">{{ $about->body }}</p>
            </div>
        </section>
    @endif

    <section class="store-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Seleção da casa</p>
                <h2>Saindo do forno</h2>
            </div>
            <a class="button button--ghost" href="{{ route('catalog.index') }}">Ver todo o catálogo</a>
        </div>
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
