<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IzipayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('App\\Services\\IzipayService')) {
            $this->markTestSkipped('IzipayService no existe en esta base de código.');
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_charge_and_subscribe_creates_paid_payment_and_new_subscription_in_sandbox_mode()
    {
        config()->set('izipay.mode', 'sandbox');
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2025-01-01 12:00:00'));

        $user = User::factory()->create(['email' => 'user@test.com']);
        $previous = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_type' => 'free',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDay(),
        ]);

        $service = app(\App\Services\IzipayService::class);
        $subscription = $service->chargeAndSubscribe([
            'user_id' => $user->id,
            'plan_type' => 'pro',
            'duration_months' => 2,
            'email_to' => $user->email,
            'method' => 'yape-qr',
        ]);

        $this->assertEquals('pro', $subscription->plan_type);
        $this->assertEquals(Carbon::now()->addMonths(2)->toDateString(), $subscription->ends_at->toDateString());

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'plan_type' => 'pro',
            'status' => 'paid',
            'provider' => 'izipay-sandbox',
            'method' => 'yape-qr',
        ]);

        $payment = Payment::where('user_id', $user->id)->latest()->first();
        $this->assertNotEmpty($payment->reference);
        $this->assertTrue(isset($payment->payload['sandbox']));

        $previous->refresh();
        $this->assertTrue($previous->ends_at->lt(Carbon::now()), 'Previous subscription should be closed.');
    }

    public function test_charge_and_subscribe_for_guest_generates_guest_id_and_payment_with_email()
    {
        config()->set('izipay.mode', 'sandbox');
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2025-02-15 08:00:00'));

        $guestEmail = 'guest@example.com';

        $service = app(\App\Services\IzipayService::class);
        $subscription = $service->chargeAndSubscribe([
            'guest_email' => $guestEmail,
            'plan_type' => 'basic',
            'duration_months' => 1,
            'email_to' => $guestEmail,
            'method' => 'yape-qr',
        ]);

        $this->assertNull($subscription->user_id);
        $this->assertEquals(md5($guestEmail), $subscription->guest_id);
        $this->assertEquals('basic', $subscription->plan_type);
        $this->assertEquals(Carbon::now()->addMonth()->toDateString(), $subscription->ends_at->toDateString());

        $this->assertDatabaseHas('payments', [
            'user_id' => null,
            'guest_email' => $guestEmail,
            'plan_type' => 'basic',
            'status' => 'paid',
        ]);
    }
}
