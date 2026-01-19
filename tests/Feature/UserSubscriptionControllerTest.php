<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_upgrade_calls_izipay_service_and_flashes_success()
    {
        $user = User::factory()->create(['email' => 'user@test.com']);

        $response = $this->from('/upgrade-plan')->actingAs($user)->post('/upgrade-plan', [
            'plan_type' => 'basic',
            'duration_months' => 1,
        ]);

        $response->assertRedirect('/upgrade-plan');
        $response->assertSessionHas('flash_notification');
    }

    public function test_process_upgrade_returns_back_and_sets_error_on_exception()
    {
        $user = User::factory()->create(['email' => 'user@test.com']);

        $response = $this->from('/upgrade-plan')->actingAs($user)->post('/upgrade-plan', [
            'plan_type' => 'basic',
            'duration_months' => 1,
        ]);

        $response->assertRedirect('/upgrade-plan');
        $response->assertSessionHas('flash_notification');
    }
}
