<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreContactRequest;
use App\Models\StoreContact;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class StoreContactController extends Controller
{
    public function edit(): \Illuminate\View\View
    {
        return view('admin.store-contact.edit', ['contact' => StoreContact::current()]);
    }

    public function update(UpdateStoreContactRequest $request): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $contact = StoreContact::query()->updateOrCreate(['id' => 1], array_replace(
                array_fill_keys((new StoreContact)->getFillable(), null),
                $request->validated(),
            ));
            AuditLogger::record('store_contact.updated', $contact);
        });

        return redirect()->route('store-contact.edit')->with('success', 'Informações de contato atualizadas.');
    }
}
