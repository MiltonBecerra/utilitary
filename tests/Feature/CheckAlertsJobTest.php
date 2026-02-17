<?php

namespace Tests\Feature;

use App\Jobs\CheckAlertsJob;
use App\Models\Alert;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckAlertsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_alert_is_marked_triggered_after_two_days_of_alerting_window()
    {
        $source = ExchangeSource::factory()->create([
            'name' => 'Kambista',
            'url' => 'https://kambista.com',
            'selector_buy' => '.buy',
            'selector_sell' => '.sell',
            'is_active' => true,
        ]);

        ExchangeRate::create([
            'exchange_source_id' => $source->id,
            'buy_price' => 3.950,
            'sell_price' => 3.970,
            'currency_from' => 'PEN',
            'currency_to' => 'USD',
        ]);

        $alert = Alert::create([
            'guest_id' => 'guest-recurring-window',
            'exchange_source_id' => $source->id,
            'target_price' => 4.100,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => 'guest@example.com',
            'status' => 'active',
            'frequency' => 'recurring',
            'recurring_window_started_at' => now()->subDays(2),
        ]);

        (new CheckAlertsJob())->handle();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'triggered',
        ]);
    }

    public function test_recurring_alert_sets_popup_pending_when_condition_is_met()
    {
        $utility = Utility::create([
            'name' => 'Currency Alert',
            'slug' => 'currency-alert',
            'description' => 'Test utility',
            'icon' => 'fas fa-dollar-sign',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_type' => 'basic',
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(10)->toDateString(),
        ]);
        $subscription->utilities()->attach($utility->id);

        $source = ExchangeSource::factory()->create([
            'name' => 'Kambista',
            'url' => 'https://kambista.com',
            'selector_buy' => '.buy',
            'selector_sell' => '.sell',
            'is_active' => true,
        ]);

        ExchangeRate::create([
            'exchange_source_id' => $source->id,
            'buy_price' => 3.950,
            'sell_price' => 3.970,
            'currency_from' => 'PEN',
            'currency_to' => 'USD',
        ]);

        $alert = Alert::create([
            'user_id' => $user->id,
            'exchange_source_id' => $source->id,
            'target_price' => 4.100,
            'condition' => 'below',
            'channel' => 'email',
            'contact_detail' => $user->email,
            'status' => 'active',
            'frequency' => 'recurring',
            'recurring_popup_pending' => false,
        ]);

        (new CheckAlertsJob())->handle();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
            'recurring_popup_pending' => 1,
        ]);
    }
}
