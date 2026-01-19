<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ExchangeSource;
use App\Models\User;
use App\Modules\Core\Services\GuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyAlertAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function createSource(): ExchangeSource
    {
        return ExchangeSource::factory()->create([
            'name' => 'Kambista',
            'url' => 'https://kambista.com',
            'selector_buy' => '.buy',
            'selector_sell' => '.sell',
            'is_active' => true,
        ]);
    }

    private function bindGuestService(string $guestId, string $plan = 'pro'): void
    {
        $fake = new class($guestId, $plan) extends GuestService {
            public function __construct(private string $fakeGuestId, private string $fakePlan) {}
            public function getGuestId() { return $this->fakeGuestId; }
            public function getGuestPlan($utilityId = null) { return $this->fakePlan; }
            public function canGuestCreateAlert($utilityId = null) { return true; }
            public function canGuestUseWhatsApp($utilityId = null) { return $this->fakePlan === 'pro'; }
            public function canGuestUseRecurringAlerts($utilityId = null) { return in_array($this->fakePlan, ['basic', 'pro']); }
            public function getGuestAlertLimit($utilityId = null) { return -1; }
            public function hasGuestReachedMonthlyAlertLimit($utilityId = null): bool { return false; }
            public function getGuestMonthlyAlertLimit($utilityId = null) { return -1; }
        };

        $this->app->instance(GuestService::class, $fake);
    }

    public function test_create_alert_returns_json_and_not_redirect()
    {
        $user = User::factory()->create();
        $source = $this->createSource();

        $payload = [
            'exchange_source_id' => $source->id,
            'target_price' => 3.755,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'test@example.com',
            'frequency' => 'once',
        ];

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson(route('currency-alert.store'), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Alerta creada exitosamente!',
                'alert' => [
                    'exchange_source_id' => $source->id,
                    'target_price' => '3.76',
                    'condition' => 'above',
                    'channel' => 'email',
                    'frequency' => 'once',
                ],
            ])
            ->assertHeaderMissing('Location');

        $this->assertDatabaseHas('alerts', [
            'exchange_source_id' => $source->id,
            'target_price' => 3.755,
            'condition' => 'above',
            'channel' => 'email',
        ]);
    }

    public function test_update_alert_returns_json_and_not_redirect()
    {
        $user = User::factory()->create();
        $source = $this->createSource();
        $alert = Alert::create([
            'user_id' => $user->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.700,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'old@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $payload = [
            'exchange_source_id' => $source->id,
            'target_price' => 3.900,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'new@example.com',
            'frequency' => 'once',
        ];

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->putJson(route('currency-alert.update', $alert->id), $payload);

        $response->assertOk()
            ->assertJson([
                'message' => 'Alerta actualizada exitosamente!',
                'alert' => [
                    'id' => $alert->id,
                    'target_price' => '3.90',
                    'condition' => 'above',
                    'channel' => 'email',
                    'contact_detail' => 'new@example.com',
                    'frequency' => 'once',
                ],
            ])
            ->assertHeaderMissing('Location');

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'target_price' => 3.900,
            'condition' => 'above',
            'contact_detail' => 'new@example.com',
            'frequency' => 'once',
        ]);
    }

    public function test_delete_alert_returns_json_and_not_redirect()
    {
        $user = User::factory()->create();
        $source = $this->createSource();
        $alert = Alert::create([
            'user_id' => $user->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.800,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'delete@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson(route('currency-alert.destroy', $alert->id));

        $response->assertOk()
            ->assertJson(['message' => 'Alerta eliminada exitosamente!'])
            ->assertHeaderMissing('Location');

        $this->assertSoftDeleted('alerts', ['id' => $alert->id]);
    }

    public function test_delete_alert_via_web_redirects_back()
    {
        $user = User::factory()->create();
        $source = $this->createSource();
        $alert = Alert::create([
            'user_id' => $user->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.800,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'delete@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $response = $this->from('/currency-alert')
            ->actingAs($user)
            ->delete(route('currency-alert.destroy', $alert->id));

        $response->assertRedirect('/currency-alert');
        $this->assertSoftDeleted('alerts', ['id' => $alert->id]);
    }

    public function test_guest_create_alert_returns_json()
    {
        $guestId = 'guest-123';
        $source = $this->createSource();
        $this->bindGuestService($guestId);

        $payload = [
            'exchange_source_id' => $source->id,
            'target_price' => 3.700,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'guest@example.com',
            'frequency' => 'once',
        ];

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson(route('currency-alert.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Alerta creada exitosamente!',
                'exchange_source_id' => $source->id,
            ])
            ->assertHeaderMissing('Location');

        $this->assertDatabaseHas('alerts', [
            'guest_id' => $guestId,
            'exchange_source_id' => $source->id,
            'target_price' => 3.700,
        ]);
    }

    public function test_guest_update_alert_returns_json()
    {
        $guestId = 'guest-456';
        $source = $this->createSource();
        $this->bindGuestService($guestId);
        $alert = Alert::create([
            'guest_id' => $guestId,
            'exchange_source_id' => $source->id,
            'target_price' => 3.650,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'old@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $payload = [
            'exchange_source_id' => $source->id,
            'target_price' => 3.950,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'new@example.com',
            'frequency' => 'once',
        ];

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->putJson(route('currency-alert.update', $alert->id), $payload);

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Alerta actualizada exitosamente!',
                'target_price' => '3.95',
            ])
            ->assertHeaderMissing('Location');

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'guest_id' => $guestId,
            'target_price' => 3.950,
            'contact_detail' => 'new@example.com',
        ]);
    }

    public function test_guest_delete_alert_returns_json()
    {
        $guestId = 'guest-789';
        $source = $this->createSource();
        $this->bindGuestService($guestId);
        $alert = Alert::create([
            'guest_id' => $guestId,
            'exchange_source_id' => $source->id,
            'target_price' => 3.820,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'delete@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->deleteJson(route('currency-alert.destroy', $alert->id));

        $response->assertOk()
            ->assertJson(['message' => 'Alerta eliminada exitosamente!'])
            ->assertHeaderMissing('Location');

        $this->assertSoftDeleted('alerts', ['id' => $alert->id]);
    }

    public function test_guest_delete_alert_via_web_redirects_back()
    {
        $guestId = 'guest-redirect';
        $source = $this->createSource();
        $this->bindGuestService($guestId);
        $alert = Alert::create([
            'guest_id' => $guestId,
            'exchange_source_id' => $source->id,
            'target_price' => 3.810,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'redirect@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $response = $this->from('/currency-alert')
            ->delete(route('currency-alert.destroy', $alert->id));

        $response->assertRedirect('/currency-alert');
        $this->assertSoftDeleted('alerts', ['id' => $alert->id]);
    }
}
