@extends('layouts.restricted')

@section('content')
    <section class="panel message-detail">
        <p><a href="{{ route('messages.index') }}">Voltar às mensagens</a></p>
        <p class="eyebrow">Recebida em {{ $message->created_at->format('d/m/Y H:i') }}</p>
        <h1>{{ $message->subject }}</h1>
        <dl class="contact-data">
            <div><dt>Nome</dt><dd>{{ $message->name }}</dd></div>
            <div><dt>E-mail</dt><dd>{{ $message->email }}</dd></div>
            <div><dt>Telefone</dt><dd>{{ $message->phone ?: 'Não informado' }}</dd></div>
        </dl>
        <div class="message-body">{!! nl2br(e($message->message)) !!}</div>
        <p>Leitura: {{ $message->read_at ? $message->read_at->format('d/m/Y H:i') : 'Não lida' }}</p>
        <p>Atendimento:
            @if ($message->handled_at)
                concluído em {{ $message->handled_at->format('d/m/Y H:i') }} por {{ $message->handledBy?->name ?? 'Operador removido' }}
            @else
                pendente
            @endif
        </p>
        <form method="POST" action="{{ route('messages.update', $message) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="version" value="{{ $message->version }}">
            <label for="status">Alterar situação</label>
            <select id="status" name="status">
                @if ($message->handled_at)
                    <option value="open">Reabrir atendimento</option>
                @else
                    <option value="handled">Marcar como atendida</option>
                    <option value="unread">Marcar como não lida</option>
                @endif
            </select>
            <p><button type="submit">Atualizar mensagem</button></p>
        </form>
    </section>
@endsection
