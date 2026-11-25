<?php

namespace Tests\Feature;

use App\Models\StoreContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    private function week(): array
    {
        return array_fill_keys(array_keys(StoreContact::WEEKDAYS), ['status' => 'closed', 'intervals' => '']);
    }

    public function test_hours_are_hidden_until_configured_and_editor_can_publish_and_edit_them(): void
    {
        $this->get(route('contact.create'))->assertOk()->assertDontSee('Horário de funcionamento');
        $this->get('/')->assertOk()->assertDontSee('Horário de funcionamento');
        $this->actingAs(User::factory()->editor()->create());
        $form = $this->get(route('opening-hours.edit'))->assertOk()->assertSee('Nenhum horário cadastrado');
        foreach (StoreContact::WEEKDAYS as $label) {
            $form->assertSee($label);
        }
        $hours = $this->week();
        $hours['monday'] = ['status' => 'open', 'intervals' => "13:00–18:00\n08:00-12:00"];
        $this->put(route('opening-hours.update'), ['hours' => $hours])
            ->assertSessionHasNoErrors()->assertRedirect(route('opening-hours.edit'));

        $saved = StoreContact::current()->opening_hours;
        $this->assertCount(7, $saved);
        $this->assertSame([
            ['start' => '08:00', 'end' => '12:00'],
            ['start' => '13:00', 'end' => '18:00'],
        ], $saved['monday']['intervals']);
        $this->assertSame(['closed' => true, 'intervals' => []], $saved['sunday']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'store_contact.opening_hours_updated']);
        $this->get(route('opening-hours.edit'))->assertOk()->assertSee('08:00–12:00')->assertSee('13:00–18:00');

        auth()->logout();
        foreach (['contact.create', 'home'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Horário de funcionamento')
                ->assertSee('08:00–12:00 e 13:00–18:00')->assertSee('Domingo')->assertSee('Fechado');
        }

        $this->actingAs(User::factory()->editor()->create());
        $hours['monday'] = ['status' => 'closed', 'intervals' => 'ignored when closed'];
        $hours['saturday'] = ['status' => 'open', 'intervals' => '09:00-14:00'];
        $this->put(route('opening-hours.update'), ['hours' => $hours])->assertSessionHasNoErrors();
        $this->assertSame(['closed' => true, 'intervals' => []], StoreContact::current()->opening_hours['monday']);
        $this->get(route('contact.create'))->assertSee('09:00–14:00')->assertDontSee('08:00–12:00');
        $this->assertDatabaseCount('store_contacts', 1);
    }

    public function test_contact_settings_and_hours_do_not_overwrite_each_other(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $this->put(route('store-contact.update'), ['email' => 'loja@example.com'])->assertSessionHasNoErrors();
        $this->get(route('contact.create'))->assertDontSee('Horário de funcionamento');
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertSessionHasNoErrors();
        $this->assertSame('loja@example.com', StoreContact::current()->email);
        $saved = StoreContact::current()->opening_hours;
        $this->put(route('store-contact.update'), ['phone' => '(11) 99999-9999'])->assertSessionHasNoErrors();
        $this->assertSame($saved, StoreContact::current()->opening_hours);
        $this->get(route('contact.create'))->assertSee('Fechado')->assertSee('Horário de funcionamento');
    }

    #[DataProvider('invalidIntervals')]
    public function test_invalid_hours_are_rejected_without_changing_saved_hours(string $intervals): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertSessionHasNoErrors();
        $saved = StoreContact::current()->opening_hours;
        $hours = $this->week();
        $hours['monday'] = ['status' => 'open', 'intervals' => $intervals];
        $this->from(route('opening-hours.edit'))->put(route('opening-hours.update'), ['hours' => $hours])
            ->assertSessionHasErrors('hours.monday.intervals')->assertRedirect(route('opening-hours.edit'));
        $this->assertSame($saved, StoreContact::current()->opening_hours);
        $this->get(route('opening-hours.edit'))->assertOk()->assertSee('aria-invalid="true"', false);
    }

    public static function invalidIntervals(): array
    {
        return [
            'empty open day' => [''],
            'wrong format' => ['8:00-12:00'],
            'invalid hour' => ['24:00-25:00'],
            'invalid minute' => ['08:60-12:00'],
            'equal times' => ['12:00-12:00'],
            'inverted times' => ['18:00-08:00'],
            'overlapping' => ["08:00-12:00\n11:00-18:00"],
            'contained interval' => ["08:00-18:00\n10:00-11:00"],
            'duplicate interval' => ["08:00-12:00\n08:00-12:00"],
            'script' => ['<script>alert(1)</script>'],
        ];
    }

    public function test_adjacent_intervals_and_day_boundaries_are_allowed(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $hours = $this->week();
        $hours['monday'] = ['status' => 'open', 'intervals' => "00:00-12:00\r\n12:00-23:59"];
        $this->put(route('opening-hours.update'), ['hours' => $hours])->assertSessionHasNoErrors();
        $this->assertCount(2, StoreContact::current()->opening_hours['monday']['intervals']);
    }

    public function test_week_structure_and_statuses_are_validated(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $hours = $this->week();
        unset($hours['sunday']);
        $hours['monday']['status'] = 'invalid';
        $hours['tuesday']['intervals'] = ['08:00-12:00'];
        $this->put(route('opening-hours.update'), ['hours' => $hours])
            ->assertSessionHasErrors(['hours.sunday', 'hours.monday.status', 'hours.tuesday.intervals']);
        $this->get(route('opening-hours.edit'))->assertOk();
        $this->put(route('opening-hours.update'), ['hours' => $this->week() + ['unknown' => []]])
            ->assertSessionHasErrors('hours');
        $this->assertDatabaseCount('store_contacts', 0);
    }

    public function test_existing_panel_permissions_and_admin_two_factor_are_enforced(): void
    {
        $this->get(route('opening-hours.edit'))->assertRedirect(route('login'));
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->viewer()->create());
        $this->get(route('opening-hours.edit'))->assertForbidden();
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->get(route('opening-hours.edit'))->assertRedirect(route('two-factor.setup'));
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertRedirect(route('two-factor.setup'));
        $this->assertDatabaseCount('store_contacts', 0);
        $admin->forceFill(['two_factor_secret' => 'secret', 'two_factor_confirmed_at' => now()])->save();
        $this->get(route('opening-hours.edit'))->assertOk();
        $this->put(route('opening-hours.update'), ['hours' => $this->week()])->assertSessionHasNoErrors();
    }
}
