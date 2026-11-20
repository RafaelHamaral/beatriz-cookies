@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <h1>Guarde estes códigos</h1>
        <p>Cada código abre o painel uma vez se você perder o aplicativo autenticador. Esta é a única vez que eles aparecem.</p>
        <ul class="codes">
            @foreach ($codes as $code)
                <li><code>{{ $code }}</code></li>
            @endforeach
        </ul>
        <p><a class="button" href="{{ route('dashboard') }}">Ir para o painel</a></p>
    </section>
@endsection
