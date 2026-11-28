<div class="store-contact-details">
    @if ($storeContact->phone)
        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $storeContact->phone) }}">{{ $storeContact->phone }}</a></p>
    @endif
    @if ($storeContact->email)
        <p><a href="mailto:{{ $storeContact->email }}">{{ $storeContact->email }}</a></p>
    @endif
    @if ($storeContact->address)
        <address>{{ $storeContact->address }}</address>
    @endif
    @if ($storeContact->whatsappNumber())
        <p><a href="https://wa.me/{{ $storeContact->whatsappNumber() }}" target="_blank" rel="noopener noreferrer">Fale pelo WhatsApp</a></p>
    @endif
    @if ($storeContact->socialLinks())
        <nav class="store-social-links" aria-label="Redes sociais da loja">
            @foreach ($storeContact->socialLinks() as $label => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    @if ($storeContact->opening_hours)
        <section class="opening-hours" aria-label="Horário de funcionamento">
            <h2>Horário de funcionamento</h2>
            <dl>
                @foreach (\App\Models\StoreContact::WEEKDAYS as $day => $label)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>
                            @if ($storeContact->opening_hours[$day]['closed'])
                                Fechado
                            @else
                                {{ implode(' e ', array_map(fn ($interval) => $interval['start'].'–'.$interval['end'], $storeContact->opening_hours[$day]['intervals'])) }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif
</div>
