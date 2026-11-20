<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Beatriz & Kathy Cookies' }}</title>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}">
</head>
<body>
    <header class="wrap auth-header">
        <a class="brand" href="{{ route('home') }}">Beatriz <span>&amp;</span> Kathy<small>Cookies</small></a>
        <nav class="nav">
            <a href="{{ route('cart.index') }}">Carrinho</a>
            <a href="{{ route('catalog.index') }}">Catálogo</a>
            <a href="{{ route('contact.create') }}">Contato</a>
            <a href="{{ route('reviews.create') }}">Avaliar</a>
            @auth
                <a href="{{ route('dashboard') }}">Área restrita</a>
                <form class="inline-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button--ghost" type="submit">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}">Área da loja</a>
            @endauth
        </nav>
    </header>
    <main class="wrap">
        @include('partials.alerts')
        @yield('content')
    </main>
</body>
</html>
