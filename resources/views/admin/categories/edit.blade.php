@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('categories.index') }}">Voltar às categorias</a></p>
        <h1>Editar {{ $category->name }}</h1>
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')
            <label for="name">Nome</label>
            <input id="name" name="name" value="{{ old('name', $category->name) }}" required>
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
            <label for="sort_order">Ordem</label>
            <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $category->sort_order) }}">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ? '1' : '0') === '1')> Ativa na vitrine</label>
            <p><button type="submit">Salvar</button></p>
        </form>
    </section>
@endsection
