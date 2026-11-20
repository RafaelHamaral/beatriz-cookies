<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetOperatorPassword;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_public_and_registration_is_closed(): void
    {
        $this->get('/')->assertOk()->assertSee('Beatriz');
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_state_changing_auth_routes_use_the_web_stack(): void
    {
        $route = app('router')->getRoutes()->getByName('login.store');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains(
            ValidateCsrfToken::class,
            app(Middleware::class)->getMiddlewareGroups()['web'],
        );
    }

    public function test_guests_are_sent_to_login_and_operators_can_sign_in(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');

        $editor = User::factory()->editor()->create();

        $this->post('/login', [
            'email' => $editor->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($editor);
        $response = $this->get(route('dashboard'));
        $response->assertOk()->assertSee($editor->name);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_failed_login_does_not_reveal_which_field_and_locks_after_five_attempts(): void
    {
        $editor = User::factory()->editor()->create();

        $this->from('/login')->post('/login', [
            'email' => $editor->email,
            'password' => 'senha-errada',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => $editor->email,
                'password' => 'senha-errada',
            ]);
        }

        $this->from('/login')->post('/login', [
            'email' => $editor->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_must_confirm_two_factor_before_opening_the_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.setup'));

        $this->get(route('dashboard'))->assertRedirect(route('two-factor.setup'));
        $this->get(route('users.index'))->assertRedirect(route('two-factor.setup'));

        $this->get(route('two-factor.setup'))->assertOk()->assertSee('<svg', false);

        $code = app(Google2FA::class)->getCurrentOtp($admin->fresh()->two_factor_secret);

        $this->followingRedirects()
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertOk()
            ->assertSee('Guarde estes códigos');

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('two-factor.recovery'))->assertRedirect(route('dashboard'));
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
    }

    public function test_confirmed_admin_needs_a_second_factor_and_can_use_a_recovery_code(): void
    {
        $admin = $this->confirmTwoFactor(User::factory()->admin()->create());

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        $this->post('/dois-fatores', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/dois-fatores', ['code' => 'ABCD-EFGH'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->post('/dois-fatores', ['code' => 'ABCD-EFGH'])->assertSessionHasErrors('code');
    }

    public function test_password_reset_uses_a_generic_response_and_does_not_audit_the_token(): void
    {
        $editor = User::factory()->editor()->create();
        Notification::fake();

        $this->post('/esqueci-a-senha', ['email' => $editor->email])
            ->assertSessionHas('status');
        $this->post('/esqueci-a-senha', ['email' => 'nao-existe@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($editor, ResetOperatorPassword::class);
        Notification::assertCount(1);

        $token = Password::broker()->createToken($editor);
        $newPassword = 'NovaSenha1';

        $this->post('/redefinir-senha', [
            'token' => $token,
            'email' => $editor->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $editor->fresh()->password));

        $audit = AuditLog::query()->where('action', 'user.password_reset')->first();
        $encoded = json_encode($audit?->properties);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($token, $encoded);
        $this->assertStringNotContainsString($newPassword, $encoded);
    }

    public function test_roles_are_enforced_on_routes_and_user_management(): void
    {
        $admin = $this->confirmTwoFactor(User::factory()->admin()->create());
        $editor = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create([
            'name' => '<script>alert(1)</script>',
        ]);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($viewer)->get(route('users.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('users.index'))->assertForbidden();
        $this->actingAs($editor)->post(route('users.store'), [
            'name' => 'Outra pessoa',
            'email' => 'outra@example.com',
            'password' => 'senha-forte',
            'role' => 'admin',
        ])->assertForbidden();

        $password = 'senha-forte';
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nova editora',
            'email' => 'nova@example.com',
            'password' => $password,
            'role' => 'editor',
        ])->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'nova@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check($password, $created->password));
        $this->assertSame('editor', $created->role->value);

        $audit = AuditLog::query()->where('action', 'user.created')->first();
        $this->assertSame('nova@example.com', $audit->properties['email']);
        $this->assertArrayNotHasKey('password', $audit->properties);

        $this->actingAs($admin)->patch(route('users.update', $created), [
            'role' => 'viewer',
        ])->assertRedirect(route('users.index'));
        $this->assertSame('viewer', $created->fresh()->role->value);

        $this->actingAs($admin)->patch(route('users.update', $admin), [
            'role' => 'viewer',
        ])->assertForbidden();
        $this->assertSame('admin', $admin->fresh()->role->value);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');
        $this->assertModelExists($admin);

        $this->actingAs($admin)->delete(route('users.destroy', $created))
            ->assertRedirect(route('users.index'));
        $this->assertModelMissing($created);

        $json = $admin->fresh()->toArray();
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('two_factor_secret', $json);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $json);
    }

    public function test_editor_and_viewer_are_not_forced_through_admin_two_factor(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route('two-factor.setup'))->assertForbidden();
        $this->actingAs($editor)->get(route('dashboard'))->assertOk()->assertDontSee('Usuários');
    }
}
