<?php

namespace Tests\Feature;

use App\Models\Donation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IyzicoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_signature_validation(): void
    {
        $donation = Donation::factory()->create([
            'conversation_id' => 'conv1',
        ]);

        $payload = ['conversationId' => 'conv1', 'paymentStatus' => 'SUCCESS'];
        $secret = 'secret123';
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        config(['services.iyzico.webhook_secret' => $secret]);
        $body = json_encode($payload);
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $resp = $this->withHeader('X-Signature', $sig)
            ->postJson('/webhooks/iyzico', $payload);

        $resp->assertOk();
        $this->assertEquals('success', $donation->fresh()->status);
    }
}
