@extends('layouts.public')

@section('content')
    <div class="auth-shell auth-shell--wide">
        <section class="auth-card">
            <p class="eyebrow">Conte como foi</p>
            <h1>Avalie a Beatriz &amp; Kathy Cookies</h1>
            <p>Sua avaliação será analisada pela equipe antes de aparecer na vitrine.</p>
            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf
                <label for="name">Nome que aparecerá no site</label>
                <input id="name" name="name" maxlength="120" autocomplete="name" value="{{ old('name') }}" required>
                <label for="rating">Nota</label>
                <select id="rating" name="rating" required>
                    <option value="">Selecione de 1 a 5</option>
                    @for ($rating = 5; $rating >= 1; $rating--)
                        <option value="{{ $rating }}" @selected((int) old('rating') === $rating)>{{ $rating }} {{ $rating === 1 ? 'estrela' : 'estrelas' }}</option>
                    @endfor
                </select>
                <label for="comment">Comentário</label>
                <textarea id="comment" name="comment" rows="6" maxlength="2000" required>{{ old('comment') }}</textarea>
                <p><button type="submit">Enviar avaliação</button></p>
            </form>
        </section>
    </div>
@endsection
