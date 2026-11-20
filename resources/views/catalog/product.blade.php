@extends('layouts.public')

@section('content')
    <article class="product-page">
        <div class="product-gallery">
            @forelse ($product->images as $image)
                <img src="{{ route('products.image', $image) }}" alt="{{ $product->name }}" width="{{ $image->width }}" height="{{ $image->height }}" @unless ($image->is_cover) class="product-gallery__thumb" @endunless>
            @empty
                <span class="product-card__placeholder">Sem foto</span>
            @endforelse
        </div>
        <div class="panel">
            <p><a href="{{ route('catalog.category', $product->category) }}">{{ $product->category->name }}</a></p>
            <h1>{{ $product->name }}</h1>
            <p class="price">{{ $product->formattedPrice() }}</p>
            <p class="badge {{ $product->is_available ? 'badge--ok' : '' }}">
                {{ $product->is_available ? 'Disponível' : 'Indisponível' }}
            </p>
            @if ($product->is_available)
                <form method="POST" action="{{ route('cart.update', $product->id) }}">
                    @csrf @method('PUT')
                    <label for="quantity">Quantidade no carrinho</label>
                    <input id="quantity" type="number" name="quantity" min="1" max="99" value="{{ session('cart.'.$product->id, 1) }}" required>
                    <p><button type="submit">Adicionar ao carrinho</button></p>
                </form>
            @endif
            <h2>Descrição</h2>
            <p>{!! nl2br(e($product->description)) !!}</p>
            <h2>Ingredientes</h2>
            <p>{!! nl2br(e($product->ingredients)) !!}</p>
            <h2>Alergênicos</h2>
            <p>{!! nl2br(e($product->allergens ?: 'Nenhum alergênico informado.')) !!}</p>
        </div>
    </article>
@endsection
