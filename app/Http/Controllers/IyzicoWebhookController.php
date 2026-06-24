<?php

namespace App\Http\Controllers;

use App\Mail\DonationReceiptMail;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class IyzicoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Signature');
        $secret    = env('HMAC_WEBHOOK_SECRET');
        $computed  = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (!$signature || !hash_equals($signature, $computed)) {
            Log::warning('webhook.invalid_signature');
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        // ── Subscription recurring payment webhooks ─────────────────────────
        $eventType = $payload['iyziEventType'] ?? null;

        if ($eventType === 'subscription.order.success') {
            return $this->handleSubscriptionSuccess($payload);
        }

        if ($eventType === 'subscription.order.failure') {
            return $this->handleSubscriptionFailure($payload);
        }

        // ── Regular payment webhooks ────────────────────────────────────────
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
                'status'      => 'success',
                'completed_at' => now(),
                'raw_payload' => $payload,
            ]);
        }

        if ($status === 'FAILURE' && $donation->status !== 'failed') {
            $donation->update([
                'status'       => 'failed',
                'failed_reason' => $payload['errorMessage'] ?? 'Webhook failure',
                'raw_payload'  => $payload,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handles a successful recurring subscription payment.
     * Creates a new Donation record representing this billing cycle.
     */
    private function handleSubscriptionSuccess(array $payload)
    {
        $subRef = $payload['subscriptionReferenceCode'] ?? null;

        Log::info('webhook.subscription.order.success', ['payload' => $payload]);

        if (!$subRef) {
            return response()->json(['ok' => true]);
        }

        $subscription = Subscription::where('iyzico_sub_ref', $subRef)
            ->with('donor', 'plan')
            ->first();

        if (!$subscription) {
            Log::warning('webhook.subscription.not_found', ['sub_ref' => $subRef]);
            return response()->json(['ok' => true]);
        }

        // Create a Donation record for this recurring charge (for history tracking)
        $donation = Donation::create([
            'donor_id'        => $subscription->donor_id,
            'conversation_id' => (string) Str::uuid(),
            'amount_minor'    => $subscription->amount_minor,
            'currency'        => 'TRY',
            'status'          => 'success',
            'email'           => $subscription->donor->email,
            'full_name'       => $subscription->donor->full_name,
            'notes'           => 'Aylık otomatik bağış',
            'completed_at'    => now(),
            'raw_payload'     => $payload,
        ]);

        // Update next billing date (approx. 1 month from now)
        $subscription->update([
            'next_billing_at' => now()->addMonth(),
            'status'          => 'active',
        ]);

        // Send receipt email
        try {
            Mail::to($subscription->donor->email)->queue(new DonationReceiptMail($donation));
        } catch (\Throwable $e) {
            Log::error('webhook.subscription.receipt_mail_error', ['message' => $e->getMessage()]);
        }

        Log::info('webhook.subscription.donation_created', [
            'donation_id'     => $donation->id,
            'subscription_id' => $subscription->id,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Handles a failed recurring subscription payment.
     * Marks the subscription as payment_failed.
     */
    private function handleSubscriptionFailure(array $payload)
    {
        $subRef = $payload['subscriptionReferenceCode'] ?? null;

        Log::warning('webhook.subscription.order.failure', ['payload' => $payload]);

        if (!$subRef) {
            return response()->json(['ok' => true]);
        }

        $subscription = Subscription::where('iyzico_sub_ref', $subRef)->first();

        if ($subscription) {
            $subscription->update(['status' => 'payment_failed']);
        }

        return response()->json(['ok' => true]);
    }
}
