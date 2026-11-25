<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOpeningHoursRequest;
use App\Models\StoreContact;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpeningHoursController extends Controller
{
    public function edit(): View
    {
        return view('admin.store-contact.hours', ['contact' => StoreContact::current()]);
    }

    public function update(UpdateOpeningHoursRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $contact = StoreContact::query()->updateOrCreate(['id' => 1], ['opening_hours' => $request->schedule()]);
            AuditLogger::record('store_contact.opening_hours_updated', $contact);
        });

        return redirect()->route('opening-hours.edit')->with('success', 'Horário de funcionamento atualizado.');
    }
}
