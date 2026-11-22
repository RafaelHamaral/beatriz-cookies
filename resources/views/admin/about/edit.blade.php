@extends('layouts.restricted')

@section('content')
    <section class="panel">
        <p class="eyebrow">Conteúdo do site</p>
        <h1>Configurar Sobre</h1>
        <p>Edite a apresentação da loja exibida na página inicial.</p>
        <form method="POST" action="{{ route('about.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <label for="title">Título</label>
            <input id="title" name="title" value="{{ old('title', $about->title) }}" maxlength="160" required>
            <label for="body">Texto</label>
            <textarea id="body" name="body" rows="9" maxlength="10000" required>{{ old('body', $about->body) }}</textarea>
            <label for="image">Imagem</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="image-hint">
            <p class="hint" id="image-hint">JPEG, PNG ou WebP, até {{ config('catalog.max_bytes') / 1024 / 1024 }} MB. Cada lado deve ter entre {{ config('catalog.min_edge') }} e {{ config('catalog.max_edge') }} pixels. Enviar uma nova imagem substitui a atual.</p>
            <label for="images">Adicionar imagens ao carrossel</label>
            <input id="images" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp" aria-describedby="image-hint carousel-hint">
            <p class="hint" id="carousel-hint">Até 8 imagens no total. As novas imagens são adicionadas ao final. Com duas ou mais, a transição é automática somente na imagem; o texto fica fixo.</p>
            <div class="about-admin-gallery">
                @foreach ($about->gallery() as $key => $image)
                    <div class="current-photo">
                        <img src="{{ route('about.image', ['image' => $key]) }}" alt="{{ $about->image_alt ?: $about->title }}">
                        <label class="check"><input type="checkbox" name="remove_images[]" value="{{ $key }}" @checked(in_array($key, old('remove_images', [])))> Remover imagem {{ $loop->iteration }}</label>
                    </div>
                @endforeach
            </div>
            <label for="image_alt">Descrição da imagem para acessibilidade</label>
            <input id="image_alt" name="image_alt" value="{{ old('image_alt', $about->image_alt) }}" maxlength="255">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $about->is_active))> Exibir seção Sobre no site</label>
            <p><button type="submit">Salvar alterações</button> <a class="button button--ghost" href="{{ route('home') }}#sobre">Ver no site</a></p>
        </form>
    </section>
@endsection
