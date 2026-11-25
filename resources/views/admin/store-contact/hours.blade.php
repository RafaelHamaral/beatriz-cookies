@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Configurações do site</p>
        <h1>Horário de funcionamento</h1>
        <p>Escolha os dias de atendimento e informe um intervalo por linha. Para cadastrar uma pausa, use, por exemplo, 08:00–12:00 na primeira linha e 13:00–18:00 na segunda.</p>
        <p id="hours-help">Use o formato de 24 horas. A abertura e o fechamento devem ocorrer no mesmo dia. Nos dias marcados como Fechado, os intervalos digitados são ignorados.</p>
        @unless ($contact->opening_hours)
            <p class="hint">Nenhum horário cadastrado. A seção ficará oculta no site até você salvar.</p>
        @endunless
        <form method="POST" action="{{ route('opening-hours.update') }}">
            @csrf
            @method('PUT')
            @foreach (\App\Models\StoreContact::WEEKDAYS as $day => $label)
                @php
                    $saved = $contact->opening_hours[$day] ?? ['closed' => true, 'intervals' => []];
                    $text = implode("\n", array_map(fn ($interval) => $interval['start'].'–'.$interval['end'], $saved['intervals']));
                    $input = old("hours.$day.intervals", $text);
                @endphp
                <fieldset class="opening-hours-day">
                    <legend>{{ $label }}</legend>
                    <label for="hours-{{ $day }}-status">Atendimento</label>
                    <select id="hours-{{ $day }}-status" name="hours[{{ $day }}][status]">
                        <option value="closed" @selected(old("hours.$day.status", $saved['closed'] ? 'closed' : 'open') === 'closed')>Fechado</option>
                        <option value="open" @selected(old("hours.$day.status", $saved['closed'] ? 'closed' : 'open') === 'open')>Aberto nos intervalos abaixo</option>
                    </select>
                    <label for="hours-{{ $day }}-intervals">Intervalos de abertura e fechamento</label>
                    <textarea id="hours-{{ $day }}-intervals" name="hours[{{ $day }}][intervals]" rows="3" maxlength="2000" placeholder="08:00–12:00&#10;13:00–18:00" aria-describedby="hours-help @error("hours.$day.intervals") hours-{{ $day }}-error @enderror" @error("hours.$day.intervals") aria-invalid="true" @enderror>{{ is_string($input) ? $input : '' }}</textarea>
                    @error("hours.$day.intervals")
                        <p id="hours-{{ $day }}-error" class="opening-hours-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            @endforeach
            <p><button type="submit">Salvar horários</button> <a class="button button--ghost" href="{{ route('contact.create') }}">Ver no site</a></p>
        </form>
    </section>
@endsection
