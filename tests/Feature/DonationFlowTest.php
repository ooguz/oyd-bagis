<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Services\Payments\IyzicoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DonationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_three_d_flow_success(): void
    {
        $this->mock(IyzicoPaymentService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutForm')->andReturn([
                'status' => 'success',
                'token' => 'tok_123',
                'paymentPageUrl' => null,
                'checkoutFormContent' => '<div>iframe</div>',
            ]);
            $mock->shouldReceive('retrievePaymentResult')->andReturn([
                'status' => 'success',
                'paymentId' => 'pay_1',
                'cardLastFour' => '4242',
                'cardBrand' => 'VISA',
                'raw' => [],
            ]);
        });

        $resp = $this->post('/donate/start', [
            'amount' => '100',
            'full_name' => 'Test Kullanıcı',
            'email' => 'test@example.com',
        ]);
        $resp->assertStatus(200);

        $donation = Donation::first();
        $this->assertNotNull($donation);
        $this->assertEquals('pending', $donation->status);

        $cb = $this->post('/donate/callback', ['token' => $donation->token]);
        $cb->assertRedirect('/');
        $this->assertEquals('success', $donation->fresh()->status);
    }
}



