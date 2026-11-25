@if (request()->cookie('beatriz_cookie_notice') !== 'v1' || request()->query('cookies') === '1')
    <aside class="cookie-notice" id="aviso-cookies" aria-labelledby="cookie-notice-title" tabindex="-1">
        <div class="cookie-notice__content">
            <h2 id="cookie-notice-title">Cookies necessários</h2>
            <p>Usamos apenas cookies necessários para manter sua sessão, o carrinho e o acesso à área da loja, além de proteger os formulários. Não usamos cookies de publicidade ou análise de visitas.</p>
            <p>Ao clicar em “Entendi”, salvamos essa confirmação em um cookie por 6 meses. Você pode rever este aviso em “Preferências de cookies” no rodapé.</p>
        </div>
        <form method="POST" action="{{ route('cookies.acknowledge') }}">
            @csrf
            <input type="hidden" name="return_path" value="{{ request()->getPathInfo() }}">
            <button type="submit">Entendi</button>
        </form>
    </aside>
@endif
