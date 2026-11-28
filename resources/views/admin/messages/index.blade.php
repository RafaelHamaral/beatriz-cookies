@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Área restrita</p>
        <h1>Mensagens</h1>
        <form class="inline-filter" method="GET" action="{{ route('messages.index') }}">
            <label for="status">Situação</label>
            <select id="status" name="status">
                <option value="">Todas</option>
                <option value="unread" @selected(request('status') === 'unread')>Não lidas</option>
                <option value="open" @selected(request('status') === 'open')>Em atendimento</option>
                <option value="handled" @selected(request('status') === 'handled')>Atendidas</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Recebida</th><th>Contato</th><th>Assunto</th><th>Leitura</th><th>Atendimento</th></tr></thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr class="{{ $message->read_at ? '' : 'table-row--unread' }}">
                            <td data-label="Recebida">{{ $message->created_at->format('d/m/Y H:i') }}</td>
                            <td data-label="Contato">{{ $message->name }}<br>{{ $message->email }}</td>
                            <td data-label="Assunto"><a href="{{ route('messages.show', $message) }}">{{ $message->subject }}</a></td>
                            <td data-label="Leitura">{{ $message->read_at ? 'Lida' : 'Nova' }}</td>
                            <td data-label="Atendimento">{{ $message->handled_at ? 'Atendida' : 'Pendente' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nenhuma mensagem encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $messages->links() }}
    </section>
@endsection
