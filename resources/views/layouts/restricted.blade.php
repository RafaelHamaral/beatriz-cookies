<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Área restrita' }} — Beatriz &amp; Kathy Cookies</title>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}">
</head>
<body>
    <header class="wrap site-header">
        <a class="brand" href="{{ route('dashboard') }}">Beatriz <span>&amp;</span> Kathy<small>Área restrita</small></a>
        <nav class="nav">
            @if (! auth()->user()?->isAdmin() || auth()->user()?->hasConfirmedTwoFactor())
                <a href="{{ route('dashboard') }}">Painel</a>
            @endif
            @if (auth()->user()?->managesCatalog() && (! auth()->user()->isAdmin() || auth()->user()->hasConfirmedTwoFactor()))
                <a href="{{ route('categories.index') }}">Categorias</a>
                <a href="{{ route('products.index') }}">Produtos</a>
                <a href="{{ route('about.edit') }}">Sobre</a>
                <a href="{{ route('store-contact.edit') }}">Contato da loja</a>
                <a href="{{ route('opening-hours.edit') }}">Horário de funcionamento</a>
                <a class="nav-link-with-badge" href="{{ route('orders.index') }}">
                    Pedidos
                    @if ($pendingOrdersCount > 0)
                        <b class="notification-badge" aria-label="{{ $pendingOrdersCount }} {{ $pendingOrdersCount === 1 ? 'novo pedido' : 'novos pedidos' }}" title="{{ $pendingOrdersCount }} {{ $pendingOrdersCount === 1 ? 'novo pedido aguardando confirmação' : 'novos pedidos aguardando confirmação' }}">
                            {{ $pendingOrdersCount > 99 ? '99+' : $pendingOrdersCount }}
                        </b>
                    @endif
                </a>
                <a class="nav-link-with-badge" href="{{ route('messages.index') }}">
                    Mensagens
                    @if ($unreadMessagesCount > 0)
                        <b class="notification-badge" aria-label="{{ $unreadMessagesCount }} {{ $unreadMessagesCount === 1 ? 'mensagem não lida' : 'mensagens não lidas' }}">
                            {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
                        </b>
                    @endif
                </a>
                <a class="nav-link-with-badge" href="{{ route('reviews.index') }}">
                    Avaliações
                    @if ($pendingReviewsCount > 0)
                        <b class="notification-badge" aria-label="{{ $pendingReviewsCount }} {{ $pendingReviewsCount === 1 ? 'avaliação pendente' : 'avaliações pendentes' }}">
                            {{ $pendingReviewsCount > 99 ? '99+' : $pendingReviewsCount }}
                        </b>
                    @endif
                </a>
            @endif
            @if (auth()->user()?->isAdmin() && auth()->user()?->hasConfirmedTwoFactor())
                <a href="{{ route('users.index') }}">Usuários</a>
            @endif
            <form class="inline-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="button--ghost" type="submit">Sair</button>
            </form>
        </nav>
    </header>
    <main class="wrap">
        @include('partials.alerts')
        @yield('content')
    </main>
</body>
</html>
