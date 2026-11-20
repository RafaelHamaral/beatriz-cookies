@extends('layouts.public')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <p class="eyebrow">Operação da loja</p>
            <h1>Acessar área restrita</h1>
            <p>Entre com o e-mail cadastrado. O cadastro público de operadores está fechado.</p>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <p><button type="submit">Entrar</button></p>
            </form>
            <p><a href="{{ route('password.request') }}">Esqueci minha senha</a></p>
        </div>
    </section>
@endsection
