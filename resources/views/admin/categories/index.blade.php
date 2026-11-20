@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('dashboard') }}">Voltar ao painel</a></p>
        <h1>Categorias</h1>
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            <label for="name">Nome</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            <label for="sort_order">Ordem</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')> Ativa na vitrine</label>
            <p><button type="submit">Criar categoria</button></p>
        </form>
    </section>

    <section class="panel" style="margin-top: 1rem;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Produtos</th>
                        <th>Vitrine</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>{{ $category->is_active ? 'Ativa' : 'Inativa' }}</td>
                            <td>
                                <a href="{{ route('categories.edit', $category) }}">Editar</a>
                                <form class="inline-form" method="POST" action="{{ route('categories.activation', $category) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $category->is_active ? '0' : '1' }}">
                                    <button type="submit">{{ $category->is_active ? 'Desativar' : 'Ativar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
