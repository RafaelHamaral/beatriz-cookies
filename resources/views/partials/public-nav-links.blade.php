<a href="{{ route('home') }}">Início</a>
<a href="{{ route('home') }}#sobre">Sobre</a>
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
