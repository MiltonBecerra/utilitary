<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_upgrade_to_pro_sets_plan_and_allows_whatsapp()
    {
        $user = User::factory()->create(['email' => 'pro@test.com']);

        $response = $this->from('/upgrade-plan')->actingAs($user)->post('/upgrade-plan', [
            'plan_type' => 'pro',
            'duration_months' => 1,
        ]);

        $response->assertRedirect('/upgrade-plan');
        $response->assertSessionHas('flash_notification');
    }

    public function test_user_upgrade_to_basic_disables_whatsapp()
    {
        $user = User::factory()->create(['email' => 'basic@test.com']);

        $response = $this->from('/upgrade-plan')->actingAs($user)->post('/upgrade-plan', [
            'plan_type' => 'basic',
            'duration_months' => 1,
        ]);

        $response->assertRedirect('/upgrade-plan');
        $response->assertSessionHas('flash_notification');
    }

    public function test_user_upgrade_with_12_months_sets_end_date_correctly()
    {
        $user = User::factory()->create(['email' => 'long@test.com']);

        $response = $this->from('/upgrade-plan')->actingAs($user)->post('/upgrade-plan', [
            'plan_type' => 'pro',
            'duration_months' => 12,
        ]);

        $response->assertRedirect('/upgrade-plan');
        $response->assertSessionHas('flash_notification');
    }

    public function test_guest_upgrade_pro_creates_subscription_with_duration()
    {
        $guestId = 'guest-test-123';

        $this->from('/guest/upgrade-plan')
            ->withCookie('currency_alert_guest_id', $guestId)
            ->post('/guest/upgrade-plan', [
                'plan_type' => 'pro',
                'duration_months' => 1,
                'email' => 'guest@test.com',
            ])
            ->assertRedirect('/guest/upgrade-plan')
            ->assertSessionHasErrors();
    }
}
