@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('products.index') }}">Voltar aos produtos</a></p>
        <h1>Editar {{ $product->name }}</h1>
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.products._form')
            <p><button type="submit">Salvar</button></p>
        </form>
    </section>
@endsection
