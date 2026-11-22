@extends('layouts.public')

@section('content')
    <section class="panel">
        <p class="eyebrow">Catálogo</p>
        <h1>O que está na vitrine</h1>
        <p>Explore nossos cookies e doces e escolha os seus favoritos.</p>
    </section>

    @forelse ($categories as $category)
        <section class="store-section">
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
