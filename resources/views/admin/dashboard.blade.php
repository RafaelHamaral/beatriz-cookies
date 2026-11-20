@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Área restrita / painel</p>
        <h1>Olá, {{ auth()->user()->name }}</h1>
        <p>Seu perfil é {{ auth()->user()->role->label() }}. {{ auth()->user()->role->description() }}</p>

        <form class="period-filter" method="GET" action="{{ route('dashboard') }}">
            <div>
                <label for="from">De</label>
                <input id="from" name="from" type="date" value="{{ $from }}" required>
            </div>
            <div>
                <label for="to">Até</label>
                <input id="to" name="to" type="date" value="{{ $to }}" required>
            </div>
            <button type="submit">Aplicar período</button>
        </form>
        <p class="hint">Faturamento e unidades usam a data da confirmação do pagamento. Pedidos pendentes usam a data de recebimento.</p>
    </section>

    <div class="metric-cards">
        <article class="metric-card">
            <span>Faturamento</span>
            <strong>{{ \App\Support\Money::format($metrics['revenue_cents']) }}</strong>
            <small>Somente pedidos pagos, sem cancelados ou estornados.</small>
        </article>
        <article class="metric-card">
            <span>Unidades vendidas</span>
            <strong>{{ $metrics['units_sold'] }}</strong>
            <small>Itens dos mesmos pedidos considerados no faturamento.</small>
        </article>
        <article class="metric-card">
            <span>Pedidos pendentes</span>
            <strong>{{ $metrics['pending_orders'] }}</strong>
            <small>Recebidos no período e aguardando confirmação.</small>
        </article>
    </div>

    <div class="dashboard-grid">
        <section class="panel">
            <h2>Movimento no período</h2>
            @if ($metrics['chart'] === [])
                <p>Nenhum pagamento válido foi confirmado neste período.</p>
            @else
                @php($maxRevenue = max(1, max(array_column($metrics['chart'], 'revenue_cents'))))
                <div class="bar-chart" aria-label="Faturamento diário no período">
                    @foreach ($metrics['chart'] as $date => $day)
                        <div class="bar-chart__row">
                            <time datetime="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</time>
                            <div class="bar-chart__track">
                                <span style="width: {{ max(4, ($day['revenue_cents'] / $maxRevenue) * 100) }}%"></span>
                            </div>
                            <strong>{{ \App\Support\Money::format($day['revenue_cents']) }}</strong>
                            <small>{{ $day['units'] }} un.</small>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <h2>Produtos mais vendidos</h2>
            @if ($metrics['ranking']->isEmpty())
                <p>O ranking aparecerá após pagamentos válidos no período.</p>
            @else
                <ol class="ranking">
                    @foreach ($metrics['ranking'] as $product)
                        <li><span>{{ $product->product_name }}</span><strong>{{ $product->units }} un.</strong></li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    @if (auth()->user()->managesCatalog())
        <div class="menu">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}">Usuários<span>Criar operadores e definir admin, editor ou leitor.</span></a>
            @endif
            <a href="{{ route('categories.index') }}">Categorias<span>Organize a vitrine e ative só o que pode aparecer.</span></a>
            <a href="{{ route('products.index') }}">Produtos<span>Fotos, preço, ingredientes e disponibilidade.</span></a>
            <a href="{{ route('orders.index') }}">
                Pedidos
                @if ($pendingOrdersCount > 0)<b class="notification-badge">{{ $pendingOrdersCount > 99 ? '99+' : $pendingOrdersCount }}</b>@endif
                <span>{{ $pendingOrdersCount > 0 ? $pendingOrdersCount.' '.($pendingOrdersCount === 1 ? 'pedido aguarda' : 'pedidos aguardam').' confirmação.' : 'Atendimento e confirmação manual de pagamento.' }}</span>
            </a>
            <a href="{{ route('messages.index') }}">
                Mensagens
                @if ($unreadMessagesCount > 0)<b class="notification-badge">{{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}</b>@endif
                <span>{{ $unreadMessagesCount > 0 ? $unreadMessagesCount.' '.($unreadMessagesCount === 1 ? 'mensagem não lida.' : 'mensagens não lidas.') : 'Caixa de contato sem novas mensagens.' }}</span>
            </a>
            <a href="{{ route('reviews.index') }}">
                Avaliações
                @if ($pendingReviewsCount > 0)<b class="notification-badge">{{ $pendingReviewsCount > 99 ? '99+' : $pendingReviewsCount }}</b>@endif
                <span>{{ $pendingReviewsCount > 0 ? $pendingReviewsCount.' '.($pendingReviewsCount === 1 ? 'avaliação aguarda' : 'avaliações aguardam').' moderação.' : 'Nenhuma avaliação pendente.' }}</span>
            </a>
        </div>
    @endif
@endsection
