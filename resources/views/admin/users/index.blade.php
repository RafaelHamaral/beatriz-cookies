@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p><a href="{{ route('dashboard') }}">Voltar ao painel</a></p>
        <h1>Operadores</h1>
        <p>Somente administradores gerenciam estas contas. A pessoa não altera o próprio perfil nem se exclui.</p>

        <h2>Novo operador</h2>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <label for="name">Nome</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <label for="role">Perfil</label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                        {{ $role->value }} — {{ $role->description() }}
                    </option>
                @endforeach
            </select>
            <p><button type="submit">Criar operador</button></p>
        </form>
    </section>

    <section class="panel" style="margin-top: 1rem;">
        <h2>Contas existentes</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->is(auth()->user()))
                                    {{ $user->role->value }}
                                @else
                                    <form class="inline-fields" method="POST" action="{{ route('users.update', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" aria-label="Perfil de {{ $user->name }}">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->value }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Salvar</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if ($user->is(auth()->user()))
                                    Seu usuário
                                @else
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Confirma excluir este operador?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Excluir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
