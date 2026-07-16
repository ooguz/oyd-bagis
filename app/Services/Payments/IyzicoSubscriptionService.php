<?php

namespace App\Services\Payments;

use App\Models\Donor;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iyzipay\Model\Customer;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Subscription\RetrieveSubscriptionCheckoutForm;
use Iyzipay\Model\Subscription\SubscriptionCancel;
use Iyzipay\Model\Subscription\SubscriptionCardUpdate;
use Iyzipay\Model\Subscription\SubscriptionCreateCheckoutForm;
use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Request\Subscription\RetrieveSubscriptionCreateCheckoutFormRequest;
use Iyzipay\Request\Subscription\SubscriptionCancelRequest;
use Iyzipay\Request\Subscription\SubscriptionCardUpdateWithSubscriptionReferenceCodeRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateCheckoutFormRequest;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;

class IyzicoSubscriptionService
{
    public function __construct(private readonly IyzicoClient $client) {}

    /**
     * Returns the iyzico product reference code that all donation plans belong to.
     * Resolution order: IYZI_SUBSCRIPTION_PRODUCT_REF env, an already-synced plan,
     * then creating the product on iyzico.
     */
    public function ensureProduct(): string
    {
        $configured = config('services.iyzico.subscription_product_ref');
        if ($configured) {
            return $configured;
        }

        $existing = SubscriptionPlan::whereNotNull('iyzico_product_ref')->value('iyzico_product_ref');
        if ($existing) {
            return $existing;
        }

        $request = new SubscriptionCreateProductRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId((string) Str::uuid());
        $request->setName(config('app.name', 'OYD').' Aylık Bağış');
        $request->setDescription('Aylık düzenli bağış aboneliği');

        $result = SubscriptionProduct::create($request, $this->client->getOptions());

        if ($result->getStatus() !== 'success' || ! $result->getReferenceCode()) {
            Log::error('iyzico.subscription.product_create_failed', [
                'error_code' => $result->getErrorCode(),
                'error_message' => $result->getErrorMessage(),
            ]);
            throw new \RuntimeException('Abonelik ürünü oluşturulamadı: '.($result->getErrorMessage() ?: 'Bilinmeyen hata'));
        }

        Log::info('iyzico.subscription.product_created', ['ref' => $result->getReferenceCode()]);

        return $result->getReferenceCode();
    }

    /**
     * Finds or creates a monthly plan (local + iyzico) for the given amount.
     */
    public function ensurePlan(int $amountMinor): SubscriptionPlan
    {
        $plan = SubscriptionPlan::where('amount_minor', $amountMinor)
            ->where('interval', 'monthly')
            ->first();

        if ($plan && $plan->iyzico_plan_ref) {
            return $plan;
        }

        $productRef = $this->ensureProduct();
        $name = 'Aylık ₺'.number_format($amountMinor / 100, 2, ',', '.').' Bağış';

        $request = new SubscriptionCreatePricingPlanRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId((string) Str::uuid());
        $request->setProductReferenceCode($productRef);
        $request->setName($name);
        $request->SetPrice(number_format($amountMinor / 100, 2, '.', ''));
        $request->setCurrencyCode('TRY');
        $request->setPaymentInterval('MONTHLY');
        $request->setPaymentIntervalCount(1);
        $request->setPlanPaymentType('RECURRING');

        $result = SubscriptionPricingPlan::create($request, $this->client->getOptions());

        if ($result->getStatus() !== 'success' || ! $result->getReferenceCode()) {
            Log::error('iyzico.subscription.plan_create_failed', [
                'amount_minor' => $amountMinor,
                'error_code' => $result->getErrorCode(),
                'error_message' => $result->getErrorMessage(),
            ]);
            throw new \RuntimeException('Abonelik planı oluşturulamadı: '.($result->getErrorMessage() ?: 'Bilinmeyen hata'));
        }

        Log::info('iyzico.subscription.plan_created', [
            'plan_ref' => $result->getReferenceCode(),
            'amount_minor' => $amountMinor,
        ]);

        $attributes = [
            'name' => $name,
            'iyzico_plan_ref' => $result->getReferenceCode(),
            'iyzico_product_ref' => $productRef,
        ];

        if ($plan) {
            $plan->update($attributes);
        } else {
            $plan = SubscriptionPlan::create($attributes + [
                'interval' => 'monthly',
                'amount_minor' => $amountMinor,
            ]);
        }

        return $plan;
    }

    /**
     * Starts the iyzico subscription checkout form.
     * Returns ['status', 'token', 'checkoutFormContent', 'errorMessage'].
     */
    public function createCheckoutForm(Donor $donor, SubscriptionPlan $plan, string $callbackUrl, string $conversationId): array
    {
        [$firstName, $lastName] = self::splitFullName($donor->full_name);

        $customer = new Customer;
        $customer->setName($firstName);
        $customer->setSurname($lastName);
        $customer->setEmail($donor->email);
        // iyzico requires GSM/identity/address for subscriptions; we only collect
        // name+email for donations, so use the same placeholders as the one-time flow.
        $customer->setGsmNumber('+905350000000');
        $customer->setIdentityNumber('11111111111');
        $customer->setBillingContactName($donor->full_name);
        $customer->setBillingCity('Istanbul');
        $customer->setBillingCountry('Turkey');
        $customer->setBillingAddress('NA');

        $request = new SubscriptionCreateCheckoutFormRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setCallbackUrl($callbackUrl);
        $request->setPricingPlanReferenceCode($plan->iyzico_plan_ref);
        $request->setSubscriptionInitialStatus('ACTIVE');
        $request->setCustomer($customer);

        try {
            $result = SubscriptionCreateCheckoutForm::create($request, $this->client->getOptions());

            Log::info('iyzico.subscription.checkout_init', [
                'donor_id' => $donor->id,
                'plan_id' => $plan->id,
                'conversation_id' => $conversationId,
                'status' => $result->getStatus(),
            ]);

            return [
                'status' => $result->getStatus(),
                'token' => $result->getToken(),
                'checkoutFormContent' => $result->getCheckoutFormContent(),
                'errorMessage' => $result->getErrorMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.subscription.checkout_init_error', [
                'donor_id' => $donor->id,
                'plan_id' => $plan->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieves the subscription created by the checkout form after the callback.
     * Returns ['status', 'subscriptionReferenceCode', 'customerReferenceCode',
     * 'subscriptionStatus', 'errorMessage'].
     */
    public function retrieveCheckoutFormResult(string $token): array
    {
        $request = new RetrieveSubscriptionCreateCheckoutFormRequest;
        $request->setLocale(Locale::TR);
        $request->setCheckoutFormToken($token);

        try {
            $result = RetrieveSubscriptionCheckoutForm::retrieve($request, $this->client->getOptions());

            Log::info('iyzico.subscription.checkout_result', [
                'token' => $token,
                'status' => $result->getStatus(),
                'subscription_status' => $result->getSubscriptionStatus(),
            ]);

            if ($result->getStatus() === 'success') {
                return [
                    'status' => 'success',
                    'subscriptionReferenceCode' => $result->getReferenceCode(),
                    'customerReferenceCode' => $result->getCustomerReferenceCode(),
                    'subscriptionStatus' => $result->getSubscriptionStatus(),
                ];
            }

            return [
                'status' => 'failure',
                'errorMessage' => $result->getErrorMessage() ?: 'Abonelik başlatılamadı',
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.subscription.checkout_result_error', [
                'token' => $token,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancels an active subscription on iyzico.
     * Returns ['status', 'errorMessage'].
     */
    public function cancelSubscription(Subscription $subscription): array
    {
        if (! $subscription->iyzico_sub_ref) {
            return ['status' => 'failure', 'errorMessage' => 'Abonelik referansı bulunamadı.'];
        }

        $request = new SubscriptionCancelRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId((string) Str::uuid());
        $request->setSubscriptionReferenceCode($subscription->iyzico_sub_ref);

        try {
            $result = SubscriptionCancel::cancel($request, $this->client->getOptions());

            Log::info('iyzico.subscription.cancel', [
                'subscription_id' => $subscription->id,
                'iyzico_ref' => $subscription->iyzico_sub_ref,
                'status' => $result->getStatus(),
            ]);

            return [
                'status' => $result->getStatus(),
                'errorMessage' => $result->getErrorMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.subscription.cancel_error', [
                'subscription_id' => $subscription->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Starts the iyzico card-update checkout form for a subscription.
     * Returns ['status', 'token', 'checkoutFormContent', 'errorMessage'].
     */
    public function createCardUpdateForm(Subscription $subscription, string $callbackUrl): array
    {
        if (! $subscription->iyzico_sub_ref) {
            return ['status' => 'failure', 'errorMessage' => 'Abonelik referansı bulunamadı.'];
        }

        $request = new SubscriptionCardUpdateWithSubscriptionReferenceCodeRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId((string) Str::uuid());
        $request->setSubscriptionReferenceCode($subscription->iyzico_sub_ref);
        $request->setCallbackUrl($callbackUrl);

        try {
            $result = SubscriptionCardUpdate::updateWithSubscriptionReferenceCode($request, $this->client->getOptions());

            Log::info('iyzico.subscription.card_update_init', [
                'subscription_id' => $subscription->id,
                'status' => $result->getStatus(),
            ]);

            return [
                'status' => $result->getStatus(),
                'token' => $result->getToken(),
                'checkoutFormContent' => $result->getCheckoutFormContent(),
                'errorMessage' => $result->getErrorMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.subscription.card_update_error', [
                'subscription_id' => $subscription->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }

    private static function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['NA', 'NA'];
        }
        $parts = explode(' ', $fullName);
        if (count($parts) === 1) {
            return [$parts[0], 'NA'];
        }
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }
}
