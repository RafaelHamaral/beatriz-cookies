@if (session('status'))
    <div class="alert alert--ok" role="status">{{ session('status') }}</div>
@endif

@if (session('success'))
    <div class="alert alert--ok" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert" role="alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
