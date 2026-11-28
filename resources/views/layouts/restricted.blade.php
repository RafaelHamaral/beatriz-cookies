<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Área restrita' }} — Beatriz &amp; Kathy Cookies</title>
    <link rel="stylesheet" href="{{ asset('css/store.css').'?v='.filemtime(public_path('css/store.css')) }}">
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
    <header class="wrap site-header">
        <a class="brand" href="{{ route('dashboard') }}">Beatriz <span>&amp;</span> Kathy<small>Área restrita</small></a>
        <nav class="nav nav--admin" aria-label="Navegação da área restrita">
            <div class="nav-bar">
                @include('partials.admin-nav-links')
            </div>
            <details class="nav-disclosure">
                <summary class="nav-toggle"><span class="nav-toggle__closed">Menu</span><span class="nav-toggle__open">Fechar</span></summary>
                <div class="nav-links">
                    @include('partials.admin-nav-links')
                </div>
            </details>
        </nav>
    </header>
    <main class="wrap" id="conteudo">
        @include('partials.alerts')
        @yield('content')
    </main>
</body>
</html>
