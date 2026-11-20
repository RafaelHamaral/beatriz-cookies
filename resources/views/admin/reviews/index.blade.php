@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Área restrita</p>
        <h1>Avaliações</h1>
        <form class="inline-filter" method="GET" action="{{ route('reviews.index') }}">
            <label for="status">Situação</label>
            <select id="status" name="status">
                <option value="">Todas</option>
                @foreach (\App\Models\Review::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit">Filtrar</button>
        </form>
        <div class="review-moderation-list">
            @forelse ($reviews as $review)
                <article class="review-moderation-card">
                    <header>
                        <div><strong>{{ $review->name }}</strong><br><span class="review-stars" aria-label="{{ $review->rating }} de 5 estrelas">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></div>
                        <span class="badge {{ $review->status === 'approved' ? 'badge--ok' : '' }}">{{ \App\Models\Review::STATUSES[$review->status] }}</span>
                    </header>
                    <p>{{ $review->comment }}</p>
                    <small>Enviada em {{ $review->created_at->format('d/m/Y H:i') }}</small>
                    <form class="inline-fields" method="POST" action="{{ route('reviews.update', $review) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="version" value="{{ $review->version }}">
                        <select name="status" aria-label="Moderação da avaliação">
                            <option value="approved">Aprovar</option>
                            <option value="rejected">Rejeitar</option>
                            @if ($review->status !== 'pending')<option value="pending">Voltar para pendente</option>@endif
                        </select>
                        <button type="submit">Salvar</button>
                    </form>
                </article>
            @empty
                <p>Nenhuma avaliação encontrada.</p>
            @endforelse
        </div>
        {{ $reviews->links() }}
    </section>
@endsection
