<article class="product-card">
    <a href="{{ route('products.show', $product) }}">
        @if ($product->coverImage)
            <img src="{{ route('products.image', $product->coverImage) }}" alt="{{ $product->name }}" width="640" height="640">
        @else
            <span class="product-card__placeholder">Sem foto</span>
        @endif
        <h3>{{ $product->name }}</h3>
    </a>
    <p>{{ $product->category->name }}</p>
    <p><strong>{{ $product->formattedPrice() }}</strong></p>
    @unless ($product->is_available)
        <p class="badge">Indisponível</p>
    @endunless
</article>
