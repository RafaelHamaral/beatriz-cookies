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
    @if ($storeContact->whatsapp)
        <p><a href="https://wa.me/{{ $storeContact->whatsapp }}" target="_blank" rel="noopener noreferrer">Fale pelo WhatsApp</a></p>
    @endif
    @if ($storeContact->socialLinks())
        <nav class="store-social-links" aria-label="Redes sociais da loja">
            @foreach ($storeContact->socialLinks() as $label => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
</div>
