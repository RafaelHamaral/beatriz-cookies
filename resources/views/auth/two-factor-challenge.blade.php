@extends('layouts.public')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <p class="eyebrow">Administrador</p>
            <h1>Confirme que é você</h1>
            <p>Digite o código de 6 dígitos do aplicativo autenticador ou um código de recuperação.</p>
            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf
                <label for="code">Código</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
                <p><button type="submit">Continuar</button></p>
            </form>
        </div>
    </section>
@endsection
