@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('dashboard') }}">Voltar ao painel</a></p>
        <h1>Produtos</h1>
        <p><a class="button" href="{{ route('products.create') }}">Novo produto</a></p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Vitrine</th>
                        <th>Encomenda</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>{{ $product->formattedPrice() }}</td>
                            <td>{{ $product->is_active ? 'Ativo' : 'Inativo' }}</td>
                            <td>{{ $product->is_available ? 'Disponível' : 'Indisponível' }}</td>
                            <td>
                                <a href="{{ route('products.edit', $product) }}">Editar</a>
                                <form class="inline-form" method="POST" action="{{ route('products.activation', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $product->is_active ? '0' : '1' }}">
                                    <button type="submit">{{ $product->is_active ? 'Desativar' : 'Ativar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection