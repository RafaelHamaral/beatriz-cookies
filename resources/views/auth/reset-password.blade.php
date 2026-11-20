@extends('layouts.public')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <h1>Nova senha</h1>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="username" required>
                <label for="password">Nova senha</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <label for="password_confirmation">Confirme a senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <p><button type="submit">Salvar senha</button></p>
            </form>
        </div>
    </section>
@endsection
