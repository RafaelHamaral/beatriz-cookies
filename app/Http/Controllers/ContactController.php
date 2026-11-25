<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\StoreNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact.create');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $message = ContactMessage::query()->create($request->validated());
            StoreNotifications::record($message);
        });

        return redirect()->route('contact.create')
            ->with('success', 'Mensagem enviada. A loja responderá pelo contato informado.');
    }
}
