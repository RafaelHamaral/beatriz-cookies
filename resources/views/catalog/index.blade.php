@extends('layouts.public')

@section('content')
    <section class="panel">
        <p class="eyebrow">Catálogo</p>
        <h1>O que está na vitrine</h1>
        <p>Somente produtos ativos, nas categorias que a loja deixou visíveis.</p>
    </section>

    @forelse ($categories as $category)
        <section>
            <h2><a href="{{ route('catalog.category', $category) }}">{{ $category->name }}</a></h2>
            @if ($category->description)
                <p>{{ $category->description }}</p>
            @endif
            <div class="product-grid">
                @foreach ($category->products as $product)
                    @include('catalog._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @empty
        <p>Nenhum produto ativo no momento.</p>
    @endforelse
@endsection
