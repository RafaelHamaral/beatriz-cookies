@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Configurações do site</p>
        <h1>Configurações de e-mail</h1>
        <p>Configure o Outlook.com para receber as notificações de novos pedidos e mensagens. Somente administradores podem alterar estas configurações.</p>
        <p><strong>Servidor:</strong> smtp-mail.outlook.com · <strong>Porta:</strong> 587 · <strong>Segurança:</strong> STARTTLS · <strong>Autenticação:</strong> OAuth2.</p>
        <p role="status">{{ $settings->refresh_token ? 'Conta autorizada. Use o envio de teste para confirmar o funcionamento.' : 'Conta ainda não conectada. Salve os dados do aplicativo e conecte o Outlook para ativar os envios.' }}</p>
        <form method="POST" action="{{ route('mail-settings.update') }}">
            @csrf
            @method('PUT')
            <label for="from_address">E-mail do remetente e conta Outlook</label>
            <input id="from_address" name="from_address" type="email" maxlength="255" value="{{ old('from_address', $settings->from_address) }}" required aria-describedby="sender-help">
            <p class="hint" id="sender-help">Ao conectar, autorize esta mesma conta Microsoft pessoal. O e-mail do visitante será usado somente para responder à mensagem.</p>
            <label for="from_name">Nome do remetente</label>
            <input id="from_name" name="from_name" maxlength="120" value="{{ old('from_name', $settings->from_name) }}" required>
            <label for="recipient">Destinatário das notificações</label>
            <input id="recipient" name="recipient" type="email" maxlength="255" value="{{ old('recipient', $settings->recipient) }}" required>
            <h2>Aplicativo Microsoft</h2>
            <p>O Outlook.com exige autorização OAuth2. A senha da caixa de e-mail não é solicitada por este site.</p>
            <ol>
                <li>No <a href="https://entra.microsoft.com/" target="_blank" rel="noopener noreferrer">Microsoft Entra</a>, registre um aplicativo que permita contas Microsoft pessoais.</li>
                <li>Adicione uma plataforma Web com esta URL de redirecionamento exata: <code>{{ route('mail-settings.callback') }}</code>. Em produção, use HTTPS e configure APP_URL com o endereço público do site.</li>
                <li>Configure a permissão delegada SMTP.Send do Office 365 Exchange Online e crie um segredo do cliente. Copie o ID do aplicativo e o <strong>valor</strong> do segredo nos campos abaixo.</li>
                <li>Salve e clique em “Conectar Outlook”. Entre com a conta do remetente e autorize o envio.</li>
            </ol>
            <label for="client_id">ID do aplicativo (cliente)</label>
            <input id="client_id" name="client_id" maxlength="36" value="{{ old('client_id', $settings->client_id) }}" placeholder="00000000-0000-0000-0000-000000000000">
            <label for="client_secret">Segredo do cliente</label>
            <input id="client_secret" name="client_secret" type="password" maxlength="4096" autocomplete="new-password" aria-describedby="secret-help">
            <p class="hint" id="secret-help">{{ $settings->client_secret ? 'Já existe um segredo salvo. Deixe em branco para mantê-lo.' : 'Informe o valor do segredo criado na Microsoft.' }} Segredos e tokens são armazenados criptografados e não são exibidos. Alterar o aplicativo, o segredo ou o remetente exige reconectar.</p>
            <p><button type="submit">Salvar configurações</button></p>
        </form>
        <div class="mail-settings-actions">
            <form method="POST" action="{{ route('mail-settings.connect') }}">
                @csrf
                <button type="submit" @disabled(! $settings->client_id || ! $settings->client_secret)>Conectar Outlook</button>
            </form>
            @if ($settings->refresh_token)
                <form method="POST" action="{{ route('mail-settings.test') }}">
                    @csrf
                    <button type="submit">Enviar dois e-mails de teste</button>
                </form>
                <form method="POST" action="{{ route('mail-settings.disconnect') }}">
                    @csrf
                    <button type="submit" class="button--ghost">Desconectar Outlook</button>
                </form>
            @endif
        </div>
        <p class="hint">O teste envia um e-mail por fluxo, com [TESTE] no assunto e dados fictícios, para o destinatário salvo. Após o envio, confira a caixa de entrada e o lixo eletrônico. Desconectar remove a autorização armazenada neste site; para revogá-la também na Microsoft, gerencie as permissões da sua conta.</p>
    </section>
@endsection
