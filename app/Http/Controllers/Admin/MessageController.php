<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageStatusRequest;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactMessage::class);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['unread', 'open', 'handled'])],
        ]);

        $messages = ContactMessage::query()
            ->when(($filters['status'] ?? null) === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(($filters['status'] ?? null) === 'open', fn ($query) => $query->whereNull('handled_at'))
            ->when(($filters['status'] ?? null) === 'handled', fn ($query) => $query->whereNotNull('handled_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.messages.index', compact('messages'));
    }

    public function show(ContactMessage $message): View
    {
        $this->authorize('view', $message);

        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now(), 'version' => $message->version + 1])->save();
            AuditLogger::record('message.read', $message);
            \Illuminate\Support\Facades\View::share(
                'unreadMessagesCount',
                max(0, (int) \Illuminate\Support\Facades\View::shared('unreadMessagesCount') - 1),
            );
        }

        return view('admin.messages.show', ['message' => $message->load('handledBy')]);
    }

    public function update(MessageStatusRequest $request, ContactMessage $message)
    {
        DB::transaction(function () use ($request, $message): void {
            $locked = ContactMessage::query()->whereKey($message->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->version !== (int) $request->validated('version')) {
                throw ValidationException::withMessages(['status' => 'A mensagem foi alterada por outro operador. Recarregue a página.']);
            }

            $status = $request->validated('status');
            if ($status === 'handled') {
                $locked->read_at ??= now();
                $locked->handled_at = now();
                $locked->handled_by = $request->user()->id;
            } elseif ($status === 'open') {
                $locked->handled_at = null;
                $locked->handled_by = null;
            } else {
                $locked->read_at = null;
                $locked->handled_at = null;
                $locked->handled_by = null;
            }
            $locked->version++;
            $locked->save();
            AuditLogger::record('message.status_changed', $locked, ['status' => $status]);
        }, 3);

        return redirect()->route('messages.show', $message)->with('success', 'Status da mensagem atualizado.');
    }
}
