<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('head')
    <title>{{ $title ?? 'Beatriz & Kathy Cookies' }}</title>
    <link rel="stylesheet" href="{{ asset('css/store.css').'?v='.filemtime(public_path('css/store.css')) }}">
</head>
<body class="storefront">
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
    <header class="wrap site-header">
        <a class="brand" href="{{ route('home') }}">Beatriz <span>&amp;</span> Kathy<small>Cookies</small></a>
        <nav class="nav" aria-label="Navegação principal">
            <div class="nav-bar">
                @include('partials.public-nav-links')
            </div>
            <details class="nav-disclosure">
                <summary class="nav-toggle"><span class="nav-toggle__closed">Menu</span><span class="nav-toggle__open">Fechar</span></summary>
                <div class="nav-links">
                    @include('partials.public-nav-links')
                </div>
            </details>
        </nav>
    </header>
    <main class="wrap site-main" id="conteudo">
        @include('partials.alerts')
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div class="footer-brand">
                <a class="brand" href="{{ route('home') }}">Beatriz <span>&amp;</span> Kathy<small>Cookies</small></a>
                <p>Cookies artesanais e doces gourmet, feitos com carinho em pequenas fornadas.</p>
            </div>
            <nav aria-label="Navegação do rodapé">
                <h2>Navegação</h2>
                <ul>
                    <li><a href="{{ route('home') }}">Início</a></li>
                    <li><a href="{{ route('home') }}#sobre">Sobre</a></li>
                    <li><a href="{{ route('catalog.index') }}">Catálogo</a></li>
                    <li><a href="{{ route('cart.index') }}">Carrinho</a></li>
                    <li><a href="{{ route('reviews.create') }}">Deixe sua avaliação</a></li>
                    <li><a href="{{ request()->fullUrlWithQuery(['cookies' => '1']) }}#aviso-cookies">Preferências de cookies</a></li>
                </ul>
            </nav>
            <div>
                <h2>Contato e encomendas</h2>
                @include('partials.store-contact')
                <p>Dúvidas sobre produtos ou uma ocasião especial? Fale com a nossa equipe.</p>
                <a class="footer-contact" href="{{ route('contact.create') }}">Enviar mensagem</a>
                <p class="footer-note">Entrega, retirada e pagamento são combinados com a loja na confirmação do pedido.</p>
            </div>
        </div>
        <div class="wrap footer-bottom">
            <small>© {{ date('Y') }} H.A Software. Todos os direitos reservados.</small>
            <a href="{{ route('dashboard') }}">Área da loja</a>
        </div>
    </footer>
    @include('partials.cookie-notice')
</body>
</html>
