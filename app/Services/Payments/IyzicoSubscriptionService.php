<?php

namespace App\Services\Payments;

use App\Models\Donor;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles all iyzico Subscription API interactions.
 *
 * Flow:
 *  1. ensureProduct()           – Get or create the iyzico "OYD Bağış" product (cached).
 *  2. ensurePlan(amountMinor)   – Get or create a monthly plan for the given amount.
 *  3. initializeCheckoutForm()  – Start the iyzico subscription checkout form.
 *  4. retrieveSubscriptionResult() – Query result after checkout callback.
 *  5. cancelSubscription()      – Cancel an active subscription.
 *  6. initializeCardUpdate()    – Start the card-update checkout form.
 */
class IyzicoSubscriptionService
{
    private string $baseUrl;
    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.iyzico.base_url', 'https://sandbox-api.iyzipay.com'), '/');
        $this->apiKey    = config('services.iyzico.api_key', '');
        $this->secretKey = config('services.iyzico.secret_key', '');
    }

    // -------------------------------------------------------------------------
    // Product & Plan management
    // -------------------------------------------------------------------------

    /**
     * Returns the iyzico product reference code for the donation subscription product.
     * Creates the product on the first call and caches the ref for 24h.
     */
    public function ensureProduct(): string
    {
        $cacheKey = 'iyzico_subscription_product_ref';

        // First, check if a ref is stored in the environment/config
        $envRef = config('services.iyzico.subscription_product_ref');
        if ($envRef) {
            return $envRef;
        }

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            $body = [
                'locale'         => 'tr',
                'conversationId' => (string) Str::uuid(),
                'name'           => config('app.name', 'OYD') . ' Aylık Bağış',
                'description'    => 'Aylık düzenli bağış aboneliği',
            ];

            $response = $this->post('/v2/subscription/products', $body);

            if (($response['status'] ?? '') !== 'success') {
                Log::error('iyzico.subscription.product_create_failed', ['response' => $response]);
                throw new \RuntimeException('Abonelik ürünü oluşturulamadı: ' . ($response['errorMessage'] ?? 'Bilinmeyen hata'));
            }

            $ref = $response['data']['referenceCode'] ?? null;
            if (!$ref) {
                throw new \RuntimeException('Abonelik ürünü referans kodu alınamadı.');
            }

            Log::info('iyzico.subscription.product_created', ['ref' => $ref]);
            return $ref;
        });
    }

    /**
     * Finds or creates a monthly SubscriptionPlan for the given amount.
     * Syncs with iyzico so that `iyzico_plan_ref` is always populated.
     */
    public function ensurePlan(int $amountMinor): SubscriptionPlan
    {
        $plan = SubscriptionPlan::where('amount_minor', $amountMinor)
            ->where('interval', 'monthly')
            ->first();

        if ($plan && $plan->iyzico_plan_ref) {
            return $plan;
        }

        // Need to create the plan on iyzico
        $productRef  = $this->ensureProduct();
        $amountMajor = number_format($amountMinor / 100, 2, '.', '');

        // productReferenceCode goes in the URL path, not the request body
        // Endpoint: POST /v2/subscription/products/{productReferenceCode}/pricing-plans
        $body = [
            'locale'               => 'tr',
            'conversationId'       => (string) Str::uuid(),
            'name'                 => 'Aylık ₺' . number_format($amountMinor / 100, 2, ',', '.') . ' Bağış',
            'price'                => $amountMajor,
            'currencyCode'         => 'TRY',
            'paymentInterval'      => 'MONTHLY',
            'paymentIntervalCount' => 1,
            'planPaymentType'      => 'RECURRING',
        ];

        $response = $this->post('/v2/subscription/products/' . $productRef . '/pricing-plans', $body);

        if (($response['status'] ?? '') !== 'success') {
            Log::error('iyzico.subscription.plan_create_failed', [
                'amount_minor' => $amountMinor,
                'response'     => $response,
            ]);
            throw new \RuntimeException('Abonelik planı oluşturulamadı: ' . ($response['errorMessage'] ?? 'Bilinmeyen hata'));
        }

        $planRef = $response['data']['referenceCode'] ?? null;
        if (!$planRef) {
            throw new \RuntimeException('Abonelik planı referans kodu alınamadı.');
        }

        Log::info('iyzico.subscription.plan_created', ['plan_ref' => $planRef, 'amount_minor' => $amountMinor]);

        if ($plan) {
            $plan->update([
                'iyzico_plan_ref'    => $planRef,
                'iyzico_product_ref' => $productRef,
            ]);
        } else {
            $plan = SubscriptionPlan::create([
                'name'               => 'Aylık ₺' . number_format($amountMinor / 100, 2, ',', '.') . ' Bağış',
                'interval'           => 'monthly',
                'amount_minor'       => $amountMinor,
                'iyzico_plan_ref'    => $planRef,
                'iyzico_product_ref' => $productRef,
            ]);
        }

        return $plan;
    }

    // -------------------------------------------------------------------------
    // Checkout Form initialization
    // -------------------------------------------------------------------------

    /**
     * Starts the iyzico subscription checkout form.
     * Returns ['status', 'token', 'checkoutFormContent', 'paymentPageUrl', 'errorMessage'].
     */
    public function initializeCheckoutForm(
        Donor $donor,
        SubscriptionPlan $plan,
        string $callbackUrl,
        string $conversationId
    ): array {
        [$firstName, $lastName] = $this->splitFullName($donor->full_name);

        $body = [
            'locale'                   => 'tr',
            'conversationId'           => $conversationId,
            'callbackUrl'              => $callbackUrl,
            'pricingPlanReferenceCode' => $plan->iyzico_plan_ref,
            'subscriptionInitialStatus' => 'ACTIVE',
            'customer'                 => [
                'name'            => $firstName,
                'surname'         => $lastName,
                'email'           => $donor->email,
                'gsmNumber'       => '+905350000000',  // placeholder – iyzico requires valid TR prefix
                'identityNumber'  => '11111111111',    // placeholder
                'billingAddress'  => [
                    'contactName' => $donor->full_name,
                    'city'        => 'Istanbul',
                    'country'     => 'Turkey',
                    'address'     => 'NA',
                ],
            ],
        ];

        $response = $this->post('/v2/subscription/checkoutform/initialize', $body);

        Log::info('iyzico.subscription.checkout_init', [
            'donor_id'       => $donor->id,
            'plan_id'        => $plan->id,
            'status'         => $response['status'] ?? 'unknown',
            'conversation_id' => $conversationId,
        ]);

        return [
            'status'              => $response['status'] ?? 'failure',
            'token'               => $response['token'] ?? null,
            'checkoutFormContent' => $response['checkoutFormContent'] ?? null,
            'paymentPageUrl'      => null, // subscription CF does not return paymentPageUrl
            'errorMessage'        => $response['errorMessage'] ?? null,
        ];
    }

    /**
     * Retrieves the subscription result after the checkout form callback.
     * Returns ['status', 'subscriptionReferenceCode', 'customerReferenceCode', 'errorMessage'].
     */
    public function retrieveSubscriptionResult(string $token): array
    {
        $body = [
            'locale' => 'tr',
            'token'  => $token,
        ];

        $response = $this->post('/v2/subscription/checkoutform/result', $body);

        Log::info('iyzico.subscription.checkout_result', [
            'token'  => $token,
            'status' => $response['status'] ?? 'unknown',
        ]);

        if (($response['status'] ?? '') === 'success') {
            $data = $response['data'] ?? [];
            return [
                'status'                    => 'success',
                'subscriptionReferenceCode' => $data['referenceCode'] ?? null,
                'customerReferenceCode'     => $data['customerReferenceCode'] ?? null,
                'subscriptionStatus'        => $data['subscriptionStatus'] ?? null,
                'startDate'                 => $data['startDate'] ?? null,
            ];
        }

        return [
            'status'       => 'failure',
            'errorMessage' => $response['errorMessage'] ?? 'Abonelik başlatılamadı',
        ];
    }

    // -------------------------------------------------------------------------
    // Subscription management
    // -------------------------------------------------------------------------

    /**
     * Cancels an active subscription on iyzico.
     */
    public function cancelSubscription(Subscription $subscription): bool
    {
        if (!$subscription->iyzico_sub_ref) {
            Log::warning('iyzico.subscription.cancel_no_ref', ['subscription_id' => $subscription->id]);
            return false;
        }

        $response = $this->post(
            '/v2/subscription/subscriptions/' . $subscription->iyzico_sub_ref . '/cancel',
            ['locale' => 'tr']
        );

        $success = ($response['status'] ?? '') === 'success';

        Log::info('iyzico.subscription.cancel', [
            'subscription_id' => $subscription->id,
            'iyzico_ref'      => $subscription->iyzico_sub_ref,
            'success'         => $success,
        ]);

        return $success;
    }

    /**
     * Initializes the iyzico Checkout Form for updating the subscription card.
     * Returns ['status', 'token', 'checkoutFormContent', 'errorMessage'].
     */
    public function initializeCardUpdate(Subscription $subscription, string $callbackUrl): array
    {
        $donor = $subscription->donor;
        if (!$donor || !$donor->iyzico_customer_ref) {
            Log::warning('iyzico.subscription.card_update_no_customer_ref', [
                'subscription_id' => $subscription->id,
            ]);
            return [
                'status'       => 'failure',
                'errorMessage' => 'Müşteri referansı bulunamadı.',
            ];
        }

        $body = [
            'locale'                    => 'tr',
            'callbackUrl'               => $callbackUrl,
            'customerReferenceCode'     => $donor->iyzico_customer_ref,
            'subscriptionReferenceCode' => $subscription->iyzico_sub_ref,
        ];

        $response = $this->post('/v2/subscription/card-update/checkoutform/initialize', $body);

        Log::info('iyzico.subscription.card_update_init', [
            'subscription_id' => $subscription->id,
            'status'          => $response['status'] ?? 'unknown',
        ]);

        return [
            'status'              => $response['status'] ?? 'failure',
            'token'               => $response['token'] ?? null,
            'checkoutFormContent' => $response['checkoutFormContent'] ?? null,
            'errorMessage'        => $response['errorMessage'] ?? null,
        ];
    }

    // -------------------------------------------------------------------------
    // HTTP helper (iyzico V2 REST API with IYZWSv2 auth)
    // -------------------------------------------------------------------------

    /**
     * Makes an authenticated POST request to the iyzico v2 REST API.
     *
     * Auth algorithm (matches IyziAuthV2Generator in the iyzico PHP SDK):
     *   signature = hex(HMAC-SHA256(randomKey + uriPath + requestBodyJson, secretKey))
     *   header value = base64("apiKey:{key}&randomKey:{rnd}&signature:{sig}")
     *   Authorization: IYZWSv2 {header value}
     *
     * The uriPath is extracted as everything from "/v2" onward (no query string).
     */
    private function post(string $path, array $body): array
    {
        $randomKey = uniqid();
        $bodyJson  = json_encode($body);

        // Extract the /v2/... portion of the URL, matching the SDK logic
        $fullUrl  = $this->baseUrl . $path;
        $v2Start  = strpos($fullUrl, '/v2');
        $uriPath  = $v2Start !== false ? substr($fullUrl, $v2Start) : $path;
        // Strip query string if present
        if (($qPos = strpos($uriPath, '?')) !== false) {
            $uriPath = substr($uriPath, 0, $qPos);
        }

        $dataToSign = $randomKey . $uriPath . $bodyJson;
        $signature  = bin2hex(hash_hmac('sha256', $dataToSign, $this->secretKey, true));

        $headerPayload = base64_encode(
            'apiKey:' . $this->apiKey .
            '&randomKey:' . $randomKey .
            '&signature:' . $signature
        );
        $authHeader = 'IYZWSv2 ' . $headerPayload;

        try {
            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl . $path, $body);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('iyzico.subscription.http_error', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);
            return [
                'status'       => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['NA', 'NA'];
        }
        $parts = explode(' ', $fullName);
        if (count($parts) === 1) {
            return [$parts[0], 'NA'];
        }
        $last  = array_pop($parts);
        $first = implode(' ', $parts);
        return [$first, $last];
    }
}
