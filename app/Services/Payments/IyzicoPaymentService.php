<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\CheckoutFormInitializeResource;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\Payment; 
use Iyzipay\Model\PaymentResource;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Currency;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\CreatePaymentRequest;

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
        $request->setCurrency(Currency::TL);
        $request->setBasketId($donation->id);
        $request->setPaymentGroup('PRODUCT');
        $request->setCallbackUrl(route('donate.callback'));
        $request->setForceThreeDS($isThreeD ? '1' : '0');

        // Minimal buyer fields
        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId($buyerData['id'] ?? (string) $donation->id);
        $buyer->setName($buyerData['name'] ?? $donation->full_name);
        $buyer->setSurname($buyerData['surname'] ?? 'NA');
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

        // Minimal addresses (iyzico requires billing/shipping objects even for virtual)
        $billing = new \Iyzipay\Model\Address();
        $billing->setContactName($donation->full_name);
        $billing->setCity('Istanbul');
        $billing->setCountry('Turkey');
        $billing->setAddress('NA');
        $request->setBillingAddress($billing);

        $shipping = new \Iyzipay\Model\Address();
        $shipping->setContactName($donation->full_name);
        $shipping->setCity('Istanbul');
        $shipping->setCountry('Turkey');
        $shipping->setAddress('NA');
        $request->setShippingAddress($shipping);

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
                'errorMessage' => $result->getErrorMessage(),
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

    public function createDirectPayment(Donation $donation, array $card): array
    {
        $req = new CreatePaymentRequest();
        $req->setLocale(Locale::TR);
        $req->setConversationId($donation->conversation_id);
        $req->setPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $req->setPaidPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $req->setCurrency(Currency::TL);
        $req->setBasketId($donation->id);
        $req->setPaymentGroup('PRODUCT');

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($donation->full_name);
        $paymentCard->setCardNumber($card['number']);
        $paymentCard->setExpireMonth($card['exp_month']);
        $paymentCard->setExpireYear($card['exp_year']);
        $paymentCard->setCvc($card['cvc']);
        $paymentCard->setRegisterCard(0);
        $req->setPaymentCard($paymentCard);

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId((string) $donation->id);
        $buyer->setName($donation->full_name);
        $buyer->setSurname('');
        $buyer->setEmail($donation->email);
        $buyer->setIdentityNumber('11111111111');
        $buyer->setRegistrationAddress('NA');
        $buyer->setIp(request()->ip());
        $buyer->setCity('Istanbul');
        $buyer->setCountry('Turkey');
        $req->setBuyer($buyer);

        $basketItem = new \Iyzipay\Model\BasketItem();
        $basketItem->setId('donation');
        $basketItem->setName('Bağış');
        $basketItem->setCategory1('Donation');
        $basketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
        $basketItem->setPrice(number_format($donation->amount_minor / 100, 2, '.', ''));
        $req->setBasketItems([$basketItem]);

        try {
            /** @var PaymentResource $res */
            $res = Payment::create($req, $this->client->getOptions());

            Log::info('iyzico.payment_create', [
                'donation_id' => $donation->id,
                'status' => $res->getStatus(),
            ]);

            return [
                'status' => $res->getStatus(),
                'paymentId' => $res->getPaymentId(),
                'cardLastFour' => substr($card['number'], -4),
                'cardBrand' => null,
                'raw' => [
                    'errorCode' => $res->getErrorCode(),
                    'errorMessage' => $res->getErrorMessage(),
                ],
                'errorMessage' => $res->getErrorMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico.payment_create_error', [
                'donation_id' => $donation->id,
                'message' => $e->getMessage(),
            ]);
            return [
                'status' => 'failure',
                'errorMessage' => $e->getMessage(),
            ];
        }
    }
}



