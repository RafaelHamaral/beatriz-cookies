<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Notificação do Site Beatriz</title></head>
<body>
@if ($test)
    <p><strong>TESTE — dados fictícios, sem pedido ou mensagem real.</strong></p>
@endif
@if ($record instanceof \App\Models\Order)
    <h1>Novo pedido #{{ $record->id }}</h1>
    <p>Nome: {{ $record->customer_name }}<br>E-mail: {{ $record->customer_email }}<br>Telefone: {{ $record->customer_phone }}</p>
    <ul>
        @foreach ($record->items as $item)
            <li>{{ $item->quantity }} × {{ $item->product_name }} — {{ \App\Support\Money::format($item->unit_price_cents) }} por unidade; subtotal {{ \App\Support\Money::format($item->total_cents) }}</li>
        @endforeach
    </ul>
    <p><strong>Total: {{ \App\Support\Money::format($record->total_cents) }}</strong></p>
    <p>Observações:<br>{!! nl2br(e($record->notes ?? 'Não informadas.')) !!}</p>
    <p>O pedido aguarda confirmação da loja. Entrega, retirada e pagamento serão combinados com o cliente.</p>
@else
    <h1>Nova mensagem</h1>
    <p>Nome: {{ $record->name }}<br>E-mail: {{ $record->email }}<br>Telefone: {{ $record->phone ?? 'Não informado.' }}</p>
    <p>Assunto: {{ $record->subject }}</p>
    <p>{!! nl2br(e($record->message)) !!}</p>
@endif
</body>
</html>
