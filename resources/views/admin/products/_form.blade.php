@csrf
<label for="category_id">Categoria</label>
<select id="category_id" name="category_id" required>
    <option value="">Selecione</option>
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
            {{ $category->name }}{{ $category->is_active ? '' : ' (inativa)' }}
        </option>
    @endforeach
</select>
<label for="name">Nome</label>
<input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required>
<label for="price">Preço</label>
<input id="price" name="price" inputmode="decimal" value="{{ old('price', isset($product) ? intdiv($product->price_cents, 100).','.str_pad((string) ($product->price_cents % 100), 2, '0', STR_PAD_LEFT) : '') }}" placeholder="18,50" required>
<label for="stock_units">Estoque em unidades</label>
<input id="stock_units" name="stock_units" type="number" min="0" max="100000" step="1" value="{{ old('stock_units', isset($product) ? $product->stock_units : 0) }}" required>
<p class="hint">Saldo físico deste produto. Venda avulsa desconta daqui. Se o produto for um combo, a venda desconta os itens da composição abaixo, e não este número.</p>
@php
    $savedComponents = isset($product) ? $product->components->values() : collect();
@endphp
<h2>Composição do combo</h2>
<p class="hint">Deixe as linhas em branco para um produto avulso. Para um combo, escolha cada produto e quantas unidades entram em uma unidade do combo.</p>
@for ($i = 0; $i < 6; $i++)
    @php
        $selected = old('components.'.$i.'.product_id', $savedComponents[$i]->product_id ?? '');
        $units = old('components.'.$i.'.units', $savedComponents[$i]->units ?? '');
    @endphp
    <label for="component-product-{{ $i }}">Produto {{ $i + 1 }} do combo</label>
    <select id="component-product-{{ $i }}" name="components[{{ $i }}][product_id]">
        <option value="">Nenhum</option>
        @foreach (($componentChoices ?? collect()) as $choice)
            <option value="{{ $choice->id }}" @selected((string) $selected === (string) $choice->id)>{{ $choice->name }}</option>
        @endforeach
    </select>
    <label for="component-units-{{ $i }}">Unidades deste produto no combo</label>
    <input id="component-units-{{ $i }}" name="components[{{ $i }}][units]" type="number" min="1" max="99" step="1" value="{{ $units }}">
@endfor
<label for="description">Descrição</label>
<textarea id="description" name="description" rows="4" required>{{ old('description', $product->description ?? '') }}</textarea>
<label for="ingredients">Ingredientes</label>
<textarea id="ingredients" name="ingredients" rows="3" required>{{ old('ingredients', $product->ingredients ?? '') }}</textarea>
<label for="allergens">Alergênicos</label>
<textarea id="allergens" name="allergens" rows="2">{{ old('allergens', $product->allergens ?? '') }}</textarea>
<input type="hidden" name="is_available" value="0">
<label class="check"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', empty($product) || $product->is_available ? '1' : '0') === '1')> Disponível para encomenda</label>
<input type="hidden" name="is_active" value="0">
<label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', empty($product) || $product->is_active ? '1' : '0') === '1')> Ativo na vitrine</label>

@isset($product)
    <h2>Fotos atuais</h2>
    @foreach ($product->images as $image)
        <figure class="current-photo">
            <img src="{{ route('products.image', $image) }}" alt="" width="120" height="120">
            <label class="check"><input type="radio" name="cover" value="existing:{{ $image->id }}" @checked(old('cover', $image->is_cover ? 'existing:'.$image->id : '') === 'existing:'.$image->id)> Capa</label>
            <label class="check"><input type="checkbox" name="remove[]" value="{{ $image->id }}"> Remover</label>
        </figure>
    @endforeach
@endisset

<label for="images">{{ isset($product) ? 'Novas fotos' : 'Fotos' }}</label>
<input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple {{ isset($product) ? '' : 'required' }}>
<p class="hint">JPEG, PNG ou WebP. Cada lado entre 400 e 4000 pixels, até 5 MB. O nome do arquivo é gerado aqui.</p>
@unless (isset($product))
    <input type="hidden" name="cover" value="new:0">
@endunless
@isset($product)
    <label class="check"><input type="radio" name="cover" value="new:0" @checked(old('cover') === 'new:0')> Usar a primeira foto nova como capa</label>
@endisset
