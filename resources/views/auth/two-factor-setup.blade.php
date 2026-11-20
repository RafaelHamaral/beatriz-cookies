@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Antes de abrir o painel</p>
        <h1>Ative a autenticação de dois fatores</h1>
        <p>Administradores confirmam o acesso com um aplicativo como Google Authenticator, 1Password ou Authy. Escaneie o código ou copie a chave.</p>
        <div class="qr">{!! $qrSvg !!}</div>
        <p>Chave manual: <strong class="secret">{{ $secret }}</strong></p>
        <form method="POST" action="{{ route('two-factor.confirm') }}">
            @csrf
            <label for="code">Código de 6 dígitos</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
            <p><button type="submit">Confirmar e entrar</button></p>
        </form>
    </section>
@endsection
