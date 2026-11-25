<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_and_default_seeder_leave_personal_data_tables_empty(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach (['users', 'store_contacts', 'outlook_mail_settings', 'orders', 'contact_messages'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_database_seeder_does_not_create_the_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_refuses_to_run_outside_local_and_keeps_the_password_out_of_the_error(): void
    {
        $password = 'SenhaDeTeste1';
        $this->setCredential('ADMIN_EMAIL', 'admin-teste@example.com');
        $this->setCredential('ADMIN_PASSWORD', $password);
        $this->app['env'] = 'production';

        try {
            $seeder = new AdminUserSeeder();
            $seeder->setContainer($this->app);
            $seeder->run();
            $this->fail('O seeder deveria recusar produção.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ambiente local', $exception->getMessage());
            $this->assertStringNotContainsString($password, $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_creates_a_local_admin_once_and_preserves_an_existing_account(): void
    {
        $email = 'admin-teste@example.com';
        $password = 'SenhaDeTeste1';
        $this->useLocalEnvironment();
        $this->setCredential('ADMIN_EMAIL', $email);
        $this->setCredential('ADMIN_PASSWORD', $password);
        $this->setCredential('ADMIN_NAME', 'Operador Teste');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', $email)->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);

        $originalHash = $admin->password;
        $this->seed(AdminUserSeeder::class);
        $admin->refresh();
        $this->assertSame($originalHash, $admin->password);
        $this->assertSame(UserRole::Admin, $admin->role);

        $existing = User::factory()->editor()->create([
            'email' => 'ja-existe@example.com',
            'name' => 'Conta antiga',
        ]);
        $existingHash = $existing->password;
        $this->setCredential('ADMIN_EMAIL', $existing->email);

        $this->seed(AdminUserSeeder::class);
        $existing->refresh();

        $this->assertSame(UserRole::Editor, $existing->role);
        $this->assertSame('Conta antiga', $existing->name);
        $this->assertSame($existingHash, $existing->password);
        $this->assertTrue(Hash::check('password', $existing->password));
    }

    public function test_example_env_has_blank_admin_credentials(): void
    {
        $example = (string) file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^ADMIN_EMAIL=\s*$/m', $example);
        $this->assertMatchesRegularExpression('/^ADMIN_PASSWORD=\s*$/m', $example);
    }

    private function useLocalEnvironment(): void
    {
        $this->app['env'] = 'local';
    }

    private function setCredential(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
