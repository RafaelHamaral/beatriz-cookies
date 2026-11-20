@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('products.index') }}">Voltar aos produtos</a></p>
        <h1>Novo produto</h1>
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @include('admin.products._form', ['product' => null])
            <p><button type="submit">Criar produto</button></p>
        </form>
    </section>
@endsection
