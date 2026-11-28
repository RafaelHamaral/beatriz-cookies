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
    <a href="{{ route('mail-settings.edit') }}">Configurações de e-mail</a>
@endif
<form class="inline-form" method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="button--ghost" type="submit">Sair</button>
</form>
