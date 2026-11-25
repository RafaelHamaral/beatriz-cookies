@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Configurações do site</p>
        <h1>Contato e redes sociais</h1>
        <p><a href="{{ route('opening-hours.edit') }}">Horário de funcionamento</a></p>
        <p>Essas informações aparecem no rodapé e na página de contato. Deixe em branco os campos que não deseja exibir.</p>
        <form method="POST" action="{{ route('store-contact.update') }}">
            @csrf
            @method('PUT')
            <label for="phone">Telefone</label>
            <input id="phone" name="phone" type="tel" maxlength="30" value="{{ old('phone', $contact->phone) }}" placeholder="(11) 99999-9999">
            <label for="email">E-mail da loja</label>
            <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $contact->email) }}">
            <label for="address">Endereço completo</label>
            <textarea id="address" name="address" rows="4" maxlength="1000">{{ old('address', $contact->address) }}</textarea>
            <label for="whatsapp">WhatsApp</label>
            <input id="whatsapp" name="whatsapp" type="tel" maxlength="15" value="{{ old('whatsapp', $contact->whatsapp) }}" placeholder="5511999999999" aria-describedby="whatsapp-hint">
            <p class="hint" id="whatsapp-hint">Código do país, DDD e número, somente números. Exemplo: 5511999999999.</p>
            @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $field => $label)
                <label for="{{ $field }}">{{ $label }} — link do perfil</label>
                <input id="{{ $field }}" name="{{ $field }}" type="url" maxlength="500" value="{{ old($field, $contact->$field) }}" placeholder="https://...">
            @endforeach
            <p><button type="submit">Salvar informações</button> <a class="button button--ghost" href="{{ route('contact.create') }}">Ver no site</a></p>
        </form>
    </section>
@endsection
