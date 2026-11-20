@extends('layouts.public')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <h1>Recuperar senha</h1>
            <p>Informe o e-mail do operador. Se ele existir, enviamos um link para definir outra senha.</p>
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                <p><button type="submit">Enviar link</button></p>
            </form>
            <p><a href="{{ route('login') }}">Voltar ao login</a></p>
        </div>
    </section>
@endsection
