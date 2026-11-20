@extends('layouts.public')

@section('content')
    <div class="auth-shell auth-shell--wide">
        <section class="auth-card">
            <p class="eyebrow">Fale com a loja</p>
            <h1>Contato</h1>
            <p>Envie sua dúvida ou solicitação. A equipe responderá pelo e-mail ou telefone informado.</p>
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <label for="name">Nome</label>
                <input id="name" name="name" maxlength="120" autocomplete="name" value="{{ old('name') }}" required>
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email') }}" required>
                <label for="phone">Telefone (opcional)</label>
                <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="{{ old('phone') }}">
                <label for="subject">Assunto</label>
                <input id="subject" name="subject" maxlength="160" value="{{ old('subject') }}" required>
                <label for="message">Mensagem</label>
                <textarea id="message" name="message" rows="7" maxlength="5000" required>{{ old('message') }}</textarea>
                <p><button type="submit">Enviar mensagem</button></p>
            </form>
        </section>
    </div>
@endsection
