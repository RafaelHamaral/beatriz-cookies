@extends('layouts.public')

@section('content')
    <section class="panel">
        <p><a href="{{ route('catalog.index') }}">Catálogo</a></p>
        <h1>{{ $category->name }}</h1>
        @if ($category->description)
            <p>{{ $category->description }}</p>
        @endif
    </section>

    @if ($products->isEmpty())
        <p>Esta categoria ainda não tem produtos ativos.</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @include('catalog._card', ['product' => $product])
            @endforeach
        </div>
    @endif
@endsection
