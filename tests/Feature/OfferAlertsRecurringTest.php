<?php

namespace Tests\Feature;

use App\Models\OfferAlert;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Utility;
use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OfferAlertsRecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_popup_pending_for_recurring_alert_when_condition_is_met()
    {
        Notification::fake();

        $utility = Utility::create([
            'name' => 'Alertas de ofertas',
            'slug' => 'offer-alert',
            'description' => 'Test utility',
            'icon' => 'fas fa-tag',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_type' => 'pro',
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(5)->toDateString(),
        ]);
        $subscription->utilities()->attach($utility->id);

        $alert = OfferAlert::create([
            'user_id' => $user->id,
            'utility_id' => $utility->id,
            'contact_email' => $user->email,
            'channel' => 'email',
            'url' => 'https://www.falabella.com.pe/falabella-pe/product/12345/test-producto',
            'title' => 'Producto',
            'store' => 'falabella',
            'current_price' => 180,
            'public_price' => 180,
            'price_type' => 'public',
            'target_price' => 150,
            'notify_on_any_drop' => false,
            'frequency' => 'recurring',
            'status' => 'active',
        ]);

        $scraper = $this->mock(OfferPriceScraperService::class);
        $scraper->shouldReceive('detectStore')->andReturn('falabella');
        $scraper->shouldReceive('fetchProduct')->andReturn([
            'store' => 'falabella',
            'title' => 'Producto',
            'price' => 140,
            'public_price' => 140,
            'cmr_price' => null,
            'image_url' => null,
        ]);

        $this->artisan('app:check-offer-alerts')->assertExitCode(0);

        $alert->refresh();
        $this->assertSame('active', $alert->status);
        $this->assertTrue((bool) $alert->recurring_popup_pending);
        $this->assertNotNull($alert->recurring_window_started_at);
        $this->assertNotNull($alert->last_notified_at);
    }

    public function test_command_marks_recurring_alert_as_triggered_after_two_days_window()
    {
        $utility = Utility::create([
            'name' => 'Alertas de ofertas',
            'slug' => 'offer-alert',
            'description' => 'Test utility',
            'icon' => 'fas fa-tag',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_type' => 'pro',
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(5)->toDateString(),
        ]);
        $subscription->utilities()->attach($utility->id);

        $alert = OfferAlert::create([
            'user_id' => $user->id,
            'utility_id' => $utility->id,
            'contact_email' => $user->email,
            'channel' => 'email',
            'url' => 'https://www.falabella.com.pe/falabella-pe/product/12345/test-producto',
            'title' => 'Producto',
            'store' => 'falabella',
            'current_price' => 180,
            'public_price' => 180,
            'price_type' => 'public',
            'target_price' => 150,
            'notify_on_any_drop' => false,
            'frequency' => 'recurring',
            'status' => 'active',
            'recurring_popup_pending' => true,
            'recurring_window_started_at' => now()->subDays(2),
        ]);

        $scraper = $this->mock(OfferPriceScraperService::class);
        $scraper->shouldReceive('detectStore')->never();
        $scraper->shouldReceive('fetchProduct')->never();

        $this->artisan('app:check-offer-alerts')->assertExitCode(0);

        $alert->refresh();
        $this->assertSame('triggered', $alert->status);
        $this->assertFalse((bool) $alert->recurring_popup_pending);
    }

    public function test_user_can_deactivate_offer_alert_via_popup_endpoint()
    {
        $user = User::factory()->create();
        $utility = Utility::create([
            'name' => 'Alertas de ofertas',
            'slug' => 'offer-alert',
            'description' => 'Test utility',
            'icon' => 'fas fa-tag',
            'is_active' => true,
        ]);

        $alert = OfferAlert::create([
            'user_id' => $user->id,
            'utility_id' => $utility->id,
            'contact_email' => $user->email,
            'channel' => 'email',
            'url' => 'https://www.falabella.com.pe/falabella-pe/product/12345/test-producto',
            'title' => 'Producto',
            'store' => 'falabella',
            'current_price' => 180,
            'public_price' => 180,
            'price_type' => 'public',
            'target_price' => 150,
            'notify_on_any_drop' => false,
            'frequency' => 'recurring',
            'status' => 'active',
            'recurring_popup_pending' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('offer-alerts.deactivate', $alert->id));

        $response->assertOk()->assertJson([
            'message' => 'Alerta desactivada.',
            'alert_id' => $alert->id,
        ]);

        $this->assertDatabaseHas('offer_alerts', [
            'id' => $alert->id,
            'status' => 'inactive',
            'recurring_popup_pending' => 0,
        ]);
    }
}
