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
                        <th>Estoque</th>
                        <th>Vitrine</th>
                        <th>Encomenda</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td data-label="Nome">{{ $product->name }}</td>
                            <td data-label="Categoria">{{ $product->category->name }}</td>
                            <td data-label="Preço">{{ $product->formattedPrice() }}</td>
                            <td data-label="Estoque">{{ $product->components_count > 0 ? 'Combo' : $product->stock_units.' un.' }}</td>
                            <td data-label="Vitrine">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</td>
                            <td data-label="Encomenda">{{ $product->is_available ? 'Disponível' : 'Indisponível' }}</td>
                            <td class="row-actions" data-label="Ações">
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