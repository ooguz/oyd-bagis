<?php

namespace App\Http\Controllers;

use App\Mail\DonationReceiptMail;
use App\Models\Donation;
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
        $secret = env('HMAC_WEBHOOK_SECRET');
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! $signature || ! hash_equals($signature, $computed)) {
            Log::warning('webhook.invalid_signature');

            return response()->json(['message' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        // Recurring subscription charge notifications (Merchant Subscription Notifications)
        $eventType = $payload['iyziEventType'] ?? null;
        if ($eventType === 'subscription.order.success') {
            return $this->handleSubscriptionOrderSuccess($payload);
        }
        if ($eventType === 'subscription.order.failure') {
            return $this->handleSubscriptionOrderFailure($payload);
        }

        $conversationId = $payload['conversationId'] ?? null;
        if (! $conversationId) {
            return response()->json(['ok' => true]);
        }

        $donation = Donation::where('conversation_id', $conversationId)->first();
        if (! $donation) {
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

    /**
     * Records a successful recurring charge as a Donation so it shows up in
     * the donor's history, and pushes the next billing date forward.
     */
    private function handleSubscriptionOrderSuccess(array $payload)
    {
        $subRef = $payload['subscriptionReferenceCode'] ?? null;
        $orderRef = $payload['orderReferenceCode'] ?? null;

        Log::info('webhook.subscription.order.success', ['payload' => $payload]);

        if (! $subRef) {
            return response()->json(['ok' => true]);
        }

        $subscription = Subscription::where('iyzico_sub_ref', $subRef)
            ->with('donor')
            ->first();

        if (! $subscription) {
            Log::warning('webhook.subscription.not_found', ['sub_ref' => $subRef]);

            return response()->json(['ok' => true]);
        }

        // iyzico retries webhooks; orderReferenceCode identifies the charge
        if ($orderRef && Donation::where('payment_id', $orderRef)->exists()) {
            return response()->json(['ok' => true]);
        }

        $donation = Donation::create([
            'donor_id' => $subscription->donor_id,
            'conversation_id' => (string) Str::uuid(),
            'payment_id' => $orderRef,
            'amount_minor' => $subscription->amount_minor,
            'currency' => config('payments.currency', 'TRY'),
            'status' => 'success',
            'email' => $subscription->donor->email,
            'full_name' => $subscription->donor->full_name,
            'notes' => 'Aylık otomatik bağış',
            'completed_at' => now(),
            'raw_payload' => $payload,
        ]);

        $subscription->update([
            'status' => 'active',
            'next_billing_at' => now()->addMonth(),
        ]);

        try {
            Mail::to($subscription->donor->email)->queue(new DonationReceiptMail($donation));
        } catch (\Throwable $e) {
            Log::error('webhook.subscription.receipt_mail_error', ['message' => $e->getMessage()]);
        }

        Log::info('webhook.subscription.donation_created', [
            'donation_id' => $donation->id,
            'subscription_id' => $subscription->id,
        ]);

        return response()->json(['ok' => true]);
    }

    private function handleSubscriptionOrderFailure(array $payload)
    {
        $subRef = $payload['subscriptionReferenceCode'] ?? null;

        Log::warning('webhook.subscription.order.failure', ['payload' => $payload]);

        if (! $subRef) {
            return response()->json(['ok' => true]);
        }

        Subscription::where('iyzico_sub_ref', $subRef)
            ->whereNotIn('status', ['cancelled'])
            ->update(['status' => 'payment_failed']);

        return response()->json(['ok' => true]);
    }
}
