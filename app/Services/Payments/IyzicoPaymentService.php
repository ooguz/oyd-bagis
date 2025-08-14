<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\CheckoutFormInitializeResource;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Currency;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;

class IyzicoPaymentService
{
    public function __construct(private readonly IyzicoClient $client)
    {
    }

    public function createCheckoutForm(Donation $donation, array $buyerData, bool $isThreeD): array
    {
        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($donation->conversation_id);
        $request->setPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $request->setPaidPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $request->setCurrency(Currency::TRY);
        $request->setBasketId($donation->id);
        $request->setPaymentGroup('PRODUCT');
        $request->setCallbackUrl(route('donate.callback'));
        $request->setForceThreeDS($isThreeD ? '1' : '0');

        // Minimal buyer fields
        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId($buyerData['id'] ?? (string) $donation->id);
        $buyer->setName($buyerData['name'] ?? $donation->full_name);
        $buyer->setSurname($buyerData['surname'] ?? '');
        $buyer->setGsmNumber($buyerData['gsm'] ?? null);
        $buyer->setEmail($donation->email);
        $buyer->setIdentityNumber($buyerData['identity_number'] ?? '11111111111');
        $buyer->setRegistrationAddress($buyerData['address'] ?? 'NA');
        $buyer->setIp(request()->ip());
        $buyer->setCity($buyerData['city'] ?? 'Istanbul');
        $buyer->setCountry($buyerData['country'] ?? 'Turkey');
        $request->setBuyer($buyer);

        // Required basket item (single donation item)
        $basketItem = new \Iyzipay\Model\BasketItem();
        $basketItem->setId('donation');
        $basketItem->setName('Bağış');
        $basketItem->setCategory1('Donation');
        $basketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
        $basketItem->setPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $request->setBasketItems([$basketItem]);

        try {
            /** @var CheckoutFormInitializeResource $result */
            $result = CheckoutFormInitialize::create($request, $this->client->getOptions());

            Log::info('iyzico.checkout_init', [
                'donation_id' => $donation->id,
                'conversation_id' => $donation->conversation_id,
                'status' => $result->getStatus(),
            ]);

            return [
                'status' => $result->getStatus(),
                'token' => $result->getToken(),
                'paymentPageUrl' => $result->getPaymentPageUrl(),
                'checkoutFormContent' => $result->getCheckoutFormContent(),
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.checkout_init_error', [
                'donation_id' => $donation->id,
                'conversation_id' => $donation->conversation_id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function retrievePaymentResult(string $token): array
    {
        // In real integration, use \Iyzipay\Model\CheckoutForm::retrieve(...)
        // Here we provide an interface for mocking in tests.
        return [
            'status' => 'success',
            'paymentId' => null,
            'cardLastFour' => null,
            'cardBrand' => null,
            'raw' => [],
        ];
    }
}



