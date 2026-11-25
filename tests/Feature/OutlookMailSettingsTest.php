<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\OutlookMailSetting;
use App\Models\User;
use App\Services\OutlookOAuth;
use App\Services\StoreNotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class OutlookMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
    }

    private function data(): array
    {
        return ['from_address' => 'contato@example.com', 'from_name' => 'Site Beatriz',
            'recipient' => 'contato@example.com',
            'client_id' => '12345678-1234-4321-8123-123456789012', 'client_secret' => 'fictional-client-secret'];
    }

    private function settings(): OutlookMailSetting
    {
        return OutlookMailSetting::create($this->data());
    }

    public function test_only_admin_with_two_factor_can_manage_mail_settings(): void
    {
        $this->get(route('mail-settings.edit'))->assertRedirect(route('login'));
        $this->put(route('mail-settings.update'), $this->data())->assertRedirect(route('login'));
        foreach (['editor', 'viewer'] as $role) {
            $this->actingAs(User::factory()->$role()->create());
            $this->get(route('mail-settings.edit'))->assertForbidden();
            $this->put(route('mail-settings.update'), $this->data())->assertForbidden();
            foreach (['connect', 'disconnect', 'test'] as $action) {
                $this->post(route('mail-settings.'.$action))->assertForbidden();
            }
            $this->get(route('mail-settings.callback'))->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('mail-settings.edit'))->assertRedirect(route('two-factor.setup'));
        $this->actingAs($this->admin());
        $this->get(route('mail-settings.edit'))->assertOk()->assertSee('smtp-mail.outlook.com')
            ->assertSee('OAuth2');
        $this->assertSame('', OutlookMailSetting::current()->from_address);
        $this->assertSame('', OutlookMailSetting::current()->recipient);
    }

    public function test_settings_are_saved_with_encrypted_hidden_secrets_and_blank_secret_is_preserved(): void
    {
        $this->actingAs($this->admin());
        $this->from(route('mail-settings.edit'))->put(route('mail-settings.update'), $this->data())
            ->assertSessionHasNoErrors()->assertRedirect(route('mail-settings.edit'));
        $settings = OutlookMailSetting::current();
        $this->assertSame('fictional-client-secret', $settings->client_secret);
        $this->assertStringNotContainsString('fictional-client-secret', DB::table('outlook_mail_settings')->value('client_secret'));
        $this->assertArrayNotHasKey('client_secret', $settings->toArray());
        $this->get(route('mail-settings.edit'))->assertDontSee('fictional-client-secret');
        $this->assertDatabaseHas('audit_logs', ['action' => 'mail_settings.updated']);
        $this->put(route('mail-settings.update'), array_replace($this->data(), ['client_secret' => '', 'from_name' => 'Beatriz']))
            ->assertSessionHasNoErrors();
        $this->assertSame('fictional-client-secret', $settings->fresh()->client_secret);
        $this->assertSame('Beatriz', $settings->fresh()->from_name);
    }

    public function test_invalid_settings_do_not_flash_the_client_secret(): void
    {
        $this->actingAs($this->admin());
        $this->put(route('mail-settings.update'), array_replace($this->data(), ['recipient' => 'invalid', 'client_id' => 'invalid']))
            ->assertSessionHasErrors(['recipient', 'client_id'])->assertSessionMissing('_old_input.client_secret');
        $this->assertDatabaseCount('outlook_mail_settings', 0);
    }

    public function test_connection_uses_state_and_pkce_and_saves_tokens_after_callback(): void
    {
        Http::fake([OutlookOAuth::BASE.'/token' => Http::response([
            'access_token' => 'fictional-access-token', 'refresh_token' => 'fictional-refresh-token', 'expires_in' => 3600,
        ])]);
        $this->settings();
        $this->actingAs($this->admin());
        $response = $this->post(route('mail-settings.connect'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertStringStartsWith(OutlookOAuth::BASE.'/authorize?', $response->headers->get('Location'));
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(OutlookOAuth::SCOPE, $query['scope']);
        $this->assertSame('contato@example.com', $query['login_hint']);
        $this->get(route('mail-settings.callback', ['state' => $query['state'], 'code' => 'fictional-code']))
            ->assertRedirect(route('mail-settings.edit'))->assertSessionHas('success');
        Http::assertSent(fn ($request) => $request['grant_type'] === 'authorization_code'
            && $request['client_secret'] === 'fictional-client-secret'
            && rtrim(strtr(base64_encode(hash('sha256', $request['code_verifier'], true)), '+/', '-_'), '=') === $query['code_challenge']);
        $settings = OutlookMailSetting::current();
        $this->assertSame('fictional-refresh-token', $settings->refresh_token);
        $this->assertStringNotContainsString('fictional-refresh-token', DB::table('outlook_mail_settings')->value('refresh_token'));
        $this->get(route('mail-settings.edit'))->assertDontSee('fictional-access-token')->assertDontSee('fictional-refresh-token');
        $this->get(route('mail-settings.callback', ['state' => $query['state'], 'code' => 'fictional-code']))->assertStatus(419);
    }

    public function test_invalid_expired_or_changed_oauth_state_is_rejected_without_http_requests(): void
    {
        Http::fake();
        $settings = $this->settings();
        $this->actingAs($this->admin());
        $this->post(route('mail-settings.connect'));
        $this->get(route('mail-settings.callback', ['state' => 'invalid', 'code' => 'test']))->assertStatus(419);
        $this->post(route('mail-settings.connect'));
        $pending = session('outlook_oauth');
        $this->withSession(['outlook_oauth' => array_replace($pending, ['expires' => time() - 1])])
            ->get(route('mail-settings.callback', ['state' => $pending['state'], 'code' => 'test']))->assertStatus(419);
        $this->post(route('mail-settings.connect'));
        $pending = session('outlook_oauth');
        $settings->update(['from_address' => 'changed@outlook.com']);
        $this->get(route('mail-settings.callback', ['state' => $pending['state'], 'code' => 'test']))->assertStatus(419);
        Http::assertNothingSent();
    }

    public function test_provider_errors_are_sanitized_and_cancelled_consent_does_not_connect(): void
    {
        Http::fake([OutlookOAuth::BASE.'/token' => Http::response(['error_description' => 'PRIVATE TOKEN AND SECRET'], 400)]);
        $settings = $this->settings();
        $settings->update(['refresh_token' => 'fictional-refresh-token']);
        try {
            app(OutlookOAuth::class)->accessToken();
            $this->fail('Should fail');
        } catch (RuntimeException $exception) {
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('PRIVATE TOKEN AND SECRET', (string) $exception);
        }
        $settings->update(['refresh_token' => null]);
        $this->actingAs($this->admin());
        $this->post(route('mail-settings.connect'));
        $this->get(route('mail-settings.callback', ['state' => session('outlook_oauth.state'), 'error' => 'access_denied']))
            ->assertRedirect(route('mail-settings.edit'))->assertSessionHas('error');
        $this->assertNull($settings->fresh()->refresh_token);
    }

    public function test_refresh_token_is_renewed_and_reused_while_access_token_is_valid(): void
    {
        $settings = $this->settings();
        $settings->update(['refresh_token' => 'old-refresh']);
        Http::fake([OutlookOAuth::BASE.'/token' => Http::response([
            'access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600,
        ])]);
        $this->assertSame('new-access', app(OutlookOAuth::class)->accessToken());
        $this->assertSame('new-access', app(OutlookOAuth::class)->accessToken());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'old-refresh');
        $this->assertSame('new-refresh', $settings->fresh()->refresh_token);
    }

    public function test_sender_uses_panel_recipient_sender_tls_and_xoauth2_without_changing_global_config(): void
    {
        $settings = $this->settings();
        $settings->update(['access_token' => 'fresh-access', 'refresh_token' => 'refresh', 'token_expires_at' => now()->addHour()]);
        Http::fake();
        $manager = app('mail.manager');
        $transport = new class extends EsmtpTransport
        {
            public array $messages = [];
            public array $authenticationMethods = [];
            protected function doSend(SentMessage $message): void { $this->messages[] = $message; }
            public function setAuthenticators(array $authenticators): void
            {
                parent::setAuthenticators($authenticators);
                $this->authenticationMethods = $authenticators;
            }
        };
        Mail::shouldReceive('build')->once()->andReturnUsing(function ($config) use ($manager, $transport) {
            $this->assertSame('smtp-mail.outlook.com', $config['host']);
            $this->assertSame(587, $config['port']);
            $this->assertTrue($config['require_tls']);
            $this->assertSame('fresh-access', $config['password']);
            $this->assertSame('contato@example.com', $config['username']);
            $mailer = $manager->build($config);
            $mailer->setSymfonyTransport($transport);
            return $mailer;
        });
        $original = config('mail.from.address');
        app(StoreNotificationSender::class)->send(new ContactMessage([
            'name' => 'Visitante', 'email' => 'visitor@example.com', 'subject' => 'Teste', 'message' => 'Teste fictício',
        ]));
        $email = $transport->messages[0]->getOriginalMessage();
        $this->assertSame('contato@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('contato@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('visitor@example.com', $email->getReplyTo()[0]->getAddress());
        $this->assertSame('XOAUTH2', $transport->authenticationMethods[0]->getAuthKeyword());
        $this->assertCount(1, $transport->authenticationMethods);
        $this->assertSame($original, config('mail.from.address'));
        Http::assertNothingSent();
    }

    public function test_disconnection_and_account_changes_clear_tokens_and_prevent_new_sends(): void
    {
        $settings = $this->settings();
        $this->actingAs($this->admin());
        $settings->update(['refresh_token' => 'refresh', 'access_token' => 'access', 'token_expires_at' => now()->addHour()]);
        $this->put(route('mail-settings.update'), array_replace($this->data(), ['client_secret' => '', 'recipient' => 'other@example.com']))
            ->assertSessionHasNoErrors();
        $this->assertSame('refresh', $settings->fresh()->refresh_token);
        $this->put(route('mail-settings.update'), array_replace($this->data(), ['client_secret' => '', 'from_address' => 'other@outlook.com']))
            ->assertSessionHasNoErrors();
        $this->assertNull($settings->fresh()->refresh_token);
        $settings->update(['refresh_token' => 'new-refresh', 'access_token' => 'access']);
        $this->post(route('mail-settings.disconnect'))->assertSessionHas('success');
        $this->assertNull($settings->fresh()->access_token);
        $this->assertNull($settings->fresh()->refresh_token);
        Http::fake();
        try {
            app(StoreNotificationSender::class)->send(new ContactMessage);
            $this->fail('Disconnected account must not send');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Conecte a conta Outlook', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_admin_can_run_the_two_existing_test_notifications(): void
    {
        $this->actingAs($this->admin());
        Artisan::shouldReceive('call')->with('notifications:test-smtp')->once()->andReturn(0);
        $this->from(route('mail-settings.edit'))->post(route('mail-settings.test'))
            ->assertRedirect(route('mail-settings.edit'))->assertSessionHas('success');
    }
}
