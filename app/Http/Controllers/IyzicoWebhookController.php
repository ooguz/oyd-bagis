<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IyzicoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Signature');
        $secret = env('HMAC_WEBHOOK_SECRET');
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (!$signature || !hash_equals($signature, $computed)) {
            Log::warning('webhook.invalid_signature');
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        $conversationId = $payload['conversationId'] ?? null;
        if (!$conversationId) {
            return response()->json(['ok' => true]);
        }

        $donation = Donation::where('conversation_id', $conversationId)->first();
        if (!$donation) {
            return response()->json(['ok' => true]);
        }

        $status = $payload['paymentStatus'] ?? null;
        if ($status === 'SUCCESS' && $donation->status !== 'success') {
            $donation->update([
                'status' => 'success',
                'completed_at' => now(),
                'raw_payload' => $payload,
            ]);
        }

        if ($status === 'FAILURE' && $donation->status !== 'failed') {
            $donation->update([
                'status' => 'failed',
                'failed_reason' => $payload['errorMessage'] ?? 'Webhook failure',
                'raw_payload' => $payload,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}



