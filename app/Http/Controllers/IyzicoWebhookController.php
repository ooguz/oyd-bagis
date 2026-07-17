<?php

namespace App\Http\Controllers;

use App\Mail\DonationReceiptMail;
use App\Models\Donation;
use App\Models\Subscription;
use App\Services\Payments\IyzicoSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class IyzicoWebhookController extends Controller
{
    private const SUBSCRIPTION_EVENTS = ['subscription.order.success', 'subscription.order.failure'];

    public function __construct(private readonly IyzicoSubscriptionService $subscriptions) {}

    public function handle(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['iyziEventType'] ?? null;

        if (! $this->verifySignature($request, $payload, $eventType)) {
            Log::warning('webhook.invalid_signature', [
                'event_type' => $eventType,
                'has_v3_header' => $request->hasHeader('X-IYZ-SIGNATURE-V3'),
                'has_legacy_header' => $request->hasHeader('X-Signature'),
            ]);

            return response()->json(['message' => 'unauthorized'], 401);
        }

        // Recurring subscription charge notifications (Merchant Subscription Notifications)
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
     * iyzico signs webhooks with X-IYZ-SIGNATURE-V3 (hex HMAC-SHA256, keyed by
     * the API secret, over an event-type-specific concatenation) — but only
     * once the "webhook signature" feature is enabled on the merchant account.
     * Subscription charge events arriving without a verifiable signature are
     * therefore confirmed against the iyzico API instead of being trusted.
     * X-Signature is our own scheme, kept for manual/internal calls.
     */
    private function verifySignature(Request $request, array $payload, ?string $eventType): bool
    {
        $legacy = $request->header('X-Signature');
        $legacySecret = config('services.iyzico.webhook_secret');
        if ($legacy && $legacySecret) {
            return hash_equals(base64_encode(hash_hmac('sha256', $request->getContent(), $legacySecret, true)), $legacy);
        }

        $v3 = $request->header('X-IYZ-SIGNATURE-V3');
        $expected = $v3 ? $this->computeV3Signature($payload, $eventType) : null;
        if ($v3 && $expected) {
            return hash_equals($expected, strtolower($v3));
        }

        if (in_array($eventType, self::SUBSCRIPTION_EVENTS, true)) {
            return $this->confirmSubscriptionOrder($payload, $eventType);
        }

        return false;
    }

    private function computeV3Signature(array $payload, ?string $eventType): ?string
    {
        $secretKey = config('services.iyzico.secret_key');
        if (! $secretKey) {
            return null;
        }

        if (in_array($eventType, self::SUBSCRIPTION_EVENTS, true)) {
            $merchantId = config('services.iyzico.merchant_id');
            if (! $merchantId) {
                return null;
            }
            $message = $merchantId.$secretKey.$eventType
                .($payload['subscriptionReferenceCode'] ?? '')
                .($payload['orderReferenceCode'] ?? '')
                .($payload['customerReferenceCode'] ?? '');
        } elseif (isset($payload['token'])) {
            // HPP format: hosted pages (checkout form, pay-with-iyzico)
            $message = $secretKey.$eventType
                .($payload['iyziPaymentId'] ?? '')
                .$payload['token']
                .($payload['paymentConversationId'] ?? '')
                .($payload['status'] ?? '');
        } else {
            // Direct format: NON-3DS / 3DS API payments
            $message = $secretKey.$eventType
                .($payload['paymentId'] ?? '')
                .($payload['paymentConversationId'] ?? '')
                .($payload['status'] ?? '');
        }

        return hash_hmac('sha256', $message, $secretKey);
    }

    private function confirmSubscriptionOrder(array $payload, string $eventType): bool
    {
        $subRef = $payload['subscriptionReferenceCode'] ?? null;
        $orderRef = $payload['orderReferenceCode'] ?? null;
        if (! $subRef || ! $orderRef) {
            return false;
        }

        $orderStatus = $this->subscriptions->retrieveOrderStatus($subRef, $orderRef);

        return $eventType === 'subscription.order.success'
            ? $orderStatus === 'SUCCESS'
            : in_array($orderStatus, ['FAILED', 'WAITING'], true);
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
