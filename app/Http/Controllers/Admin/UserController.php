<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->orderBy('id')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'password', 'role']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        AuditLogger::record('user.created', $user, [
            'email' => $user->email,
            'role' => $user->role->value,
        ]);

        return redirect()->route('users.index')->with('success', 'Operador criado.');
    }

    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = UserRole::from($request->string('role')->toString());

        if ($user->role !== $role) {
            $from = $user->role->value;
            $user->role = $role;
            $user->save();

            AuditLogger::record('user.role_updated', $user, [
                'from' => $from,
                'to' => $role->value,
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Perfil atualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = request()->user();

        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin)->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Não é possível excluir o último administrador.');
        }

        $this->authorize('delete', $user);

        $snapshot = [
            'email' => $user->email,
            'role' => $user->role->value,
        ];
        $user->delete();

        AuditLogger::record('user.deleted', null, $snapshot + [
            'deleted_by' => $actor?->email,
        ]);

        return redirect()->route('users.index')->with('success', 'Operador excluído.');
    }
}
