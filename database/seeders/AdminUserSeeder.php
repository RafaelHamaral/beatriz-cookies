<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('O administrador inicial só pode ser criado em ambiente local.');
        }

        $email = $this->credential('ADMIN_EMAIL');
        $password = $this->credential('ADMIN_PASSWORD');
        $name = $this->credential('ADMIN_NAME') ?? 'Administrador';

        if ($email === null || $password === null) {
            throw new RuntimeException('Defina ADMIN_EMAIL e ADMIN_PASSWORD no .env local antes de executar este seeder.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL precisa ser um e-mail válido.');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('ADMIN_PASSWORD precisa ter no mínimo 8 caracteres.');
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            $this->command?->info('Conta já existente. Senha e perfil foram preservados.');

            return;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
        ]);

        $this->command?->info('Administrador inicial criado.');
    }

    private function credential(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
