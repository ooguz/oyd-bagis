<?php

namespace Tests\Feature;

use App\Mail\SubscriptionReceiptMail;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\MagicLink;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Payments\IyzicoSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.features.subscriptions' => true]);
    }

    private function makePlan(int $amountMinor = 10000): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Aylık ₺100,00 Bağış',
            'interval' => 'monthly',
            'amount_minor' => $amountMinor,
            'iyzico_plan_ref' => 'plan_ref_1',
            'iyzico_product_ref' => 'prod_ref_1',
        ]);
    }

    private function makeActiveSubscription(Donor $donor, int $amountMinor = 10000): Subscription
    {
        return Subscription::create([
            'donor_id' => $donor->id,
            'plan_id' => $this->makePlan($amountMinor)->id,
            'amount_minor' => $amountMinor,
            'status' => 'active',
            'iyzico_sub_ref' => 'sub_ref_1',
            'started_at' => now(),
        ]);
    }

    public function test_monthly_donation_rejected_when_feature_disabled(): void
    {
        config(['payments.features.subscriptions' => false]);

        $resp = $this->from('/')->post('/donate/start', [
            'amount' => '100',
            'full_name' => 'Test Kullanıcı',
            'email' => 'test@example.com',
            'donation_type' => 'monthly',
        ]);

        $resp->assertRedirect('/');
        $resp->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_monthly_donation_start_creates_pending_subscription(): void
    {
        $this->mock(IyzicoSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('ensurePlan')->with(10000)->andReturnUsing(fn () => $this->makePlan());
            $mock->shouldReceive('createCheckoutForm')->andReturn([
                'status' => 'success',
                'token' => 'sub_tok_123',
                'checkoutFormContent' => '<div>subscription form</div>',
                'errorMessage' => null,
            ]);
        });

        $resp = $this->post('/donate/start', [
            'amount' => '100',
            'full_name' => 'Test Kullanıcı',
            'email' => 'test@example.com',
            'donation_type' => 'monthly',
        ]);

        $resp->assertStatus(200);
        $resp->assertSee('subscription form', false);

        $subscription = Subscription::first();
        $this->assertNotNull($subscription);
        $this->assertEquals('pending', $subscription->status);
        $this->assertEquals('sub_tok_123', $subscription->checkout_token);
        $this->assertEquals(10000, $subscription->amount_minor);
        $this->assertNotNull($subscription->conversation_id);
    }

    public function test_monthly_donation_rejected_when_active_subscription_with_same_amount_exists(): void
    {
        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $this->makeActiveSubscription($donor);

        $resp = $this->from('/')->post('/donate/start', [
            'amount' => '100',
            'full_name' => 'Test Kullanıcı',
            'email' => 'test@example.com',
            'donation_type' => 'monthly',
        ]);

        $resp->assertRedirect('/');
        $resp->assertSessionHasErrors('payment');
        $this->assertEquals(1, Subscription::count());
    }

    public function test_subscription_callback_activates_subscription(): void
    {
        Mail::fake();

        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = Subscription::create([
            'donor_id' => $donor->id,
            'plan_id' => $this->makePlan()->id,
            'amount_minor' => 10000,
            'conversation_id' => 'conv_sub_1',
            'status' => 'pending',
            'checkout_token' => 'sub_tok_123',
        ]);

        $this->mock(IyzicoSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('retrieveCheckoutFormResult')->with('sub_tok_123')->andReturn([
                'status' => 'success',
                'subscriptionReferenceCode' => 'iyzi_sub_ref',
                'customerReferenceCode' => 'iyzi_cust_ref',
                'subscriptionStatus' => 'ACTIVE',
            ]);
        });

        $resp = $this->post('/donate/subscription/callback', ['token' => 'sub_tok_123']);

        $resp->assertRedirect('/');
        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('iyzi_sub_ref', $subscription->iyzico_sub_ref);
        $this->assertEquals('iyzi_cust_ref', $subscription->iyzico_customer_ref);
        $this->assertNotNull($subscription->started_at);
        $this->assertNotNull($subscription->next_billing_at);
        $this->assertEquals('iyzi_cust_ref', $donor->fresh()->iyzico_customer_ref);

        Mail::assertQueued(SubscriptionReceiptMail::class);
    }

    public function test_subscription_callback_failure_marks_subscription_failed(): void
    {
        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = Subscription::create([
            'donor_id' => $donor->id,
            'plan_id' => $this->makePlan()->id,
            'amount_minor' => 10000,
            'status' => 'pending',
            'checkout_token' => 'sub_tok_123',
        ]);

        $this->mock(IyzicoSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('retrieveCheckoutFormResult')->andReturn([
                'status' => 'failure',
                'errorMessage' => 'Kart reddedildi',
            ]);
        });

        $resp = $this->post('/donate/subscription/callback', ['token' => 'sub_tok_123']);

        $resp->assertRedirect('/');
        $resp->assertSessionHasErrors('payment');
        $this->assertEquals('failed', $subscription->fresh()->status);
    }

    private function postSignedWebhook(array $payload)
    {
        $secret = 'secret123';
        putenv('HMAC_WEBHOOK_SECRET='.$secret);
        $sig = base64_encode(hash_hmac('sha256', json_encode($payload), $secret, true));

        return $this->withHeader('X-Signature', $sig)->postJson('/webhooks/iyzico', $payload);
    }

    public function test_webhook_subscription_order_success_creates_donation_once(): void
    {
        Mail::fake();

        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = $this->makeActiveSubscription($donor);

        $payload = [
            'iyziEventType' => 'subscription.order.success',
            'subscriptionReferenceCode' => 'sub_ref_1',
            'orderReferenceCode' => 'order_ref_1',
            'customerReferenceCode' => 'cust_ref_1',
        ];

        $this->postSignedWebhook($payload)->assertOk();

        $donation = Donation::first();
        $this->assertNotNull($donation);
        $this->assertEquals('success', $donation->status);
        $this->assertEquals(10000, $donation->amount_minor);
        $this->assertEquals('order_ref_1', $donation->payment_id);
        $this->assertEquals('Aylık otomatik bağış', $donation->notes);
        $this->assertNotNull($subscription->fresh()->next_billing_at);

        // Webhook retries must not duplicate the donation
        $this->postSignedWebhook($payload)->assertOk();
        $this->assertEquals(1, Donation::count());
    }

    public function test_webhook_subscription_order_failure_marks_payment_failed(): void
    {
        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = $this->makeActiveSubscription($donor);

        $this->postSignedWebhook([
            'iyziEventType' => 'subscription.order.failure',
            'subscriptionReferenceCode' => 'sub_ref_1',
            'orderReferenceCode' => 'order_ref_2',
        ])->assertOk();

        $this->assertEquals('payment_failed', $subscription->fresh()->status);
    }

    public function test_dashboard_shows_subscriptions_and_cancel_works(): void
    {
        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = $this->makeActiveSubscription($donor);

        MagicLink::create([
            'email' => 'test@example.com',
            'token' => 'magic_tok_1',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->mock(IyzicoSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('cancelSubscription')->andReturn([
                'status' => 'success',
                'errorMessage' => null,
            ]);
        });

        $page = $this->get('/donations?token=magic_tok_1');
        $page->assertOk();
        $page->assertSee('Aylık Bağış Abonelikleriniz');
        $page->assertSee('₺100,00/ay');

        // The magic link is consumed, but the session keeps the dashboard usable
        $again = $this->get('/donations');
        $again->assertOk();

        $cancel = $this->post("/donations/subscription/{$subscription->id}/cancel");
        $cancel->assertRedirect(route('donations.index'));

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_subscription_actions_require_session(): void
    {
        $donor = Donor::create(['email' => 'test@example.com', 'full_name' => 'Test Kullanıcı']);
        $subscription = $this->makeActiveSubscription($donor);

        $this->post("/donations/subscription/{$subscription->id}/cancel")->assertForbidden();
        $this->post("/donations/subscription/{$subscription->id}/card-update")->assertForbidden();
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    public function test_subscription_actions_forbidden_for_other_donors(): void
    {
        $owner = Donor::create(['email' => 'owner@example.com', 'full_name' => 'Sahip Kullanıcı']);
        $subscription = $this->makeActiveSubscription($owner);

        MagicLink::create([
            'email' => 'other@example.com',
            'token' => 'magic_tok_2',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->get('/donations?token=magic_tok_2')->assertOk();
        $this->post("/donations/subscription/{$subscription->id}/cancel")->assertForbidden();
        $this->assertEquals('active', $subscription->fresh()->status);
    }
}
