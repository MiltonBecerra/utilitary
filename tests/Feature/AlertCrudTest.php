<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ExchangeSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function exchangeSource(): ExchangeSource
    {
        return ExchangeSource::factory()->create([
            'name' => 'Kambista',
            'url' => 'https://kambista.com',
            'selector_buy' => '.buy',
            'selector_sell' => '.sell',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_alert()
    {
        $admin = $this->adminUser();
        $source = $this->exchangeSource();

        $payload = [
            'user_id' => $admin->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.758,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'test@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ];

        $response = $this->actingAs($admin)->post(route('alerts.store'), $payload);

        $response->assertRedirect(route('alerts.index'));
        $this->assertDatabaseHas('alerts', [
            'exchange_source_id' => $source->id,
            'target_price' => 3.758,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'test@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);
    }

    public function test_admin_can_update_alert()
    {
        $admin = $this->adminUser();
        $source = $this->exchangeSource();
        $alert = Alert::factory()->create([
            'user_id' => $admin->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.700,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'old@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $payload = [
            'user_id' => $admin->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.900,
            'condition' => 'above',
            'channel' => 'whatsapp',
            'contact_detail' => '+51999999999',
            'status' => 'inactive',
            'frequency' => 'recurring',
        ];

        $response = $this->actingAs($admin)->put(route('alerts.update', $alert->id), $payload);

        $response->assertRedirect(route('alerts.index'));
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'target_price' => 3.900,
            'condition' => 'above',
            'channel' => 'whatsapp',
            'contact_detail' => '+51999999999',
            'status' => 'inactive',
            'frequency' => 'recurring',
        ]);
    }

    public function test_admin_can_delete_alert()
    {
        $admin = $this->adminUser();
        $source = $this->exchangeSource();
        $alert = Alert::factory()->create([
            'user_id' => $admin->id,
            'exchange_source_id' => $source->id,
            'target_price' => 3.750,
            'condition' => 'above',
            'channel' => 'email',
            'contact_detail' => 'delete@example.com',
            'status' => 'active',
            'frequency' => 'once',
        ]);

        $response = $this->actingAs($admin)->delete(route('alerts.destroy', $alert->id));

        $response->assertRedirect(route('alerts.index'));
        $this->assertSoftDeleted('alerts', ['id' => $alert->id]);
    }
}
