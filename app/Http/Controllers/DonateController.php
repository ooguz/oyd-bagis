<?php

namespace App\Http\Controllers;

use App\Mail\AdminDonationNoticeMail;
use App\Mail\DonationReceiptMail;
use App\Mail\SubscriptionReceiptMail;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use App\Services\MobileDetectionService;
use App\Services\Payments\IyzicoPaymentService;
use App\Services\Payments\IyzicoSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DonateController extends Controller
{
    public function __construct(
        private readonly IyzicoPaymentService $payments,
        private readonly IyzicoSubscriptionService $subscriptions,
        private readonly MobileDetectionService $mobileDetection
    ) {}

    public function start(Request $request)
    {
        $rules = [
            'amount' => ['required', 'string'],
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'donation_type' => ['nullable', 'in:once,monthly'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
        if (config('payments.flow', 'checkout') === 'direct') {
            $rules['card_oneline'] = ['required', 'string', 'min:10'];
        }
        $validated = $request->validate($rules);

        $amountMinor = self::parseAmountToMinor($validated['amount']);
        if ($amountMinor < 100) {
            return back()->withErrors(['amount' => 'Minimum 1 TL tutar giriniz.'])->withInput();
        }

        if (($validated['donation_type'] ?? 'once') === 'monthly') {
            if (! config('payments.features.subscriptions')) {
                return back()->withErrors(['payment' => 'Aylık düzenli bağış şu anda kullanılamıyor.'])->withInput();
            }

            return $this->startMonthlyDonation($request, $validated, $amountMinor);
        }

        $threshold = (float) config('payments.three_d_threshold_major', 500.00);
        $isThreeD = ($amountMinor / 100) > $threshold;

        $donor = Donor::firstOrCreate(
            ['email' => $validated['email']],
            ['full_name' => $validated['full_name']]
        );

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'conversation_id' => (string) Str::uuid(),
            'amount_minor' => $amountMinor,
            'currency' => config('payments.currency', 'TRY'),
            'status' => 'pending',
            'email' => $validated['email'],
            'full_name' => $validated['full_name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        try {
            if (config('payments.flow', 'checkout') === 'direct') {
                $card = self::parseOnelineCard($validated['card_oneline']);
                if (! $card) {
                    return back()->withErrors(['card_oneline' => 'Kart bilgisi geçersiz. Lütfen "KartNo AA/YY CVC" formatında giriniz.'])->withInput();
                }

                $result = $this->payments->createDirectPayment($donation, $card);

                if (($result['status'] ?? '') === 'success') {
                    $donation->update([
                        'status' => 'success',
                        'completed_at' => now(),
                        'payment_id' => $result['paymentId'] ?? null,
                        'card_last4' => $result['cardLastFour'] ?? null,
                        'card_brand' => $result['cardBrand'] ?? null,
                        'raw_payload' => $result['raw'] ?? $result,
                    ]);

                    Mail::to($donation->email)->queue(new DonationReceiptMail($donation));
                    if (config('mail.from.address') && env('ADMIN_EMAIL')) {
                        Mail::to(env('ADMIN_EMAIL'))->queue(new AdminDonationNoticeMail($donation, [
                            'ip' => request()->ip(),
                            'ua' => $request->userAgent(),
                        ]));
                    }

                    if ($request->expectsJson()) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Payment completed',
                        ]);
                    }

                    return redirect()->route('home')->with('success', 'Bağışınız için teşekkür ederiz.');
                }

                $donation->update([
                    'status' => 'failed',
                    'failed_reason' => $result['errorMessage'] ?? 'Ödeme başarısız',
                    'raw_payload' => $result['raw'] ?? $result,
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'failure',
                        'errorMessage' => self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Ödeme başarısız'),
                    ], 422);
                }

                return back()->withErrors(['payment' => 'Ödeme başarısız oldu. Hata nedeni: '.self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata', $result['raw']['errorCode'] ?? null)])->withInput();
            }

            [$firstName, $lastName] = self::splitFullName($donor->full_name);
            $result = $this->payments->createCheckoutForm($donation, [
                'name' => $firstName,
                'surname' => $lastName,
            ], $isThreeD);

            $donation->update([
                'token' => $result['token'] ?? null,
                'raw_payload' => $result,
                'failed_reason' => ($result['status'] ?? '') !== 'success' ? ($result['errorMessage'] ?? null) : null,
            ]);

            // Check if mobile device and redirect directly to iyzico
            if ($this->mobileDetection->isMobileOrTablet($request) && ! empty($result['paymentPageUrl'])) {
                Log::info('donation.mobile_redirect', [
                    'donation_id' => $donation->id,
                    'device_type' => $this->mobileDetection->getDeviceType($request),
                    'user_agent' => $request->userAgent(),
                    'payment_page_url' => $result['paymentPageUrl'],
                ]);

                return redirect()->away($result['paymentPageUrl']);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => $result['status'] ?? 'failure',
                    'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                    'paymentPageUrl' => $result['paymentPageUrl'] ?? null,
                    'token' => $result['token'] ?? null,
                    'errorMessage' => ($result['status'] ?? '') !== 'success' ? self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Ödeme bileşeni yüklenemedi') : null,
                ], ($result['status'] ?? '') === 'success' ? 200 : 422);
            }

            return view('welcome', [
                'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                'paymentPageUrl' => $result['paymentPageUrl'] ?? null,
                'donation' => $donation,
                'successMessage' => null,
                'errorMessage' => ($result['status'] ?? '') !== 'success' ? self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Ödeme bileşeni yüklenemedi') : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('donation.start_error', [
                'donation_id' => $donation->id,
                'message' => $e->getMessage(),
            ]);

            $donation->update([
                'status' => 'failed',
                'failed_reason' => 'Ödeme başlatılamadı',
            ]);

            return back()->withErrors(['payment' => 'Ödeme başlatılırken bir hata oluştu.'])->withInput();
        }
    }

    private function startMonthlyDonation(Request $request, array $validated, int $amountMinor)
    {
        // iyzico creates a card-storage consumer account keyed by the GSM number
        // for subscriptions, so a real number is needed here.
        $phone = self::normalizeTurkishGsm($validated['phone'] ?? null);
        if (! $phone) {
            return back()->withErrors([
                'phone' => 'Aylık bağış için geçerli bir cep telefonu numarası giriniz (örn. 05xx xxx xx xx).',
            ])->withInput();
        }

        $donor = Donor::firstOrCreate(
            ['email' => $validated['email']],
            ['full_name' => $validated['full_name']]
        );

        if ($donor->phone !== $phone) {
            $donor->update(['phone' => $phone]);
        }

        $hasActiveSameAmount = $donor->subscriptions()
            ->where('status', 'active')
            ->where('amount_minor', $amountMinor)
            ->exists();

        if ($hasActiveSameAmount) {
            return back()->withErrors([
                'payment' => 'Bu tutar için zaten aktif bir aylık bağışınız mevcut. Bağış geçmişinizden yönetebilirsiniz.',
            ])->withInput();
        }

        try {
            $plan = $this->subscriptions->ensurePlan($amountMinor);
            $conversationId = (string) Str::uuid();

            $result = $this->subscriptions->createCheckoutForm(
                $donor,
                $plan,
                route('donate.subscription.callback'),
                $conversationId
            );

            if (($result['status'] ?? '') !== 'success' || empty($result['token'])) {
                Log::error('donation.monthly_init_failed', [
                    'donor_id' => $donor->id,
                    'amount_minor' => $amountMinor,
                    'error' => $result['errorMessage'] ?? 'unknown',
                ]);

                return back()->withErrors([
                    'payment' => 'Aylık bağış başlatılamadı: '.self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata'),
                ])->withInput();
            }

            $subscription = Subscription::create([
                'donor_id' => $donor->id,
                'plan_id' => $plan->id,
                'amount_minor' => $amountMinor,
                'conversation_id' => $conversationId,
                'status' => 'pending',
                'checkout_token' => $result['token'],
            ]);

            Log::info('donation.monthly_init', [
                'donor_id' => $donor->id,
                'subscription_id' => $subscription->id,
                'conversation_id' => $conversationId,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                    'token' => $result['token'],
                ]);
            }

            return view('welcome', [
                'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                'paymentPageUrl' => null,
                'donation' => null,
                'successMessage' => null,
                'errorMessage' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('donation.monthly_start_error', [
                'donor_id' => $donor->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['payment' => 'Aylık bağış başlatılırken bir hata oluştu.'])->withInput();
        }
    }

    public function subscriptionCallback(Request $request): RedirectResponse
    {
        $token = $request->input('token');

        Log::info('donate.subscription_callback', [
            'token' => $token,
            'request_data' => $request->all(),
        ]);

        if (! $token) {
            return redirect()->route('home')->withErrors(['payment' => 'Geçersiz abonelik dönüşü.']);
        }

        $subscription = Subscription::where('checkout_token', $token)
            ->with('donor')
            ->first();

        if (! $subscription) {
            Log::warning('donate.subscription_callback_not_found', ['token' => $token]);

            return redirect()->route('home')->withErrors(['payment' => 'Abonelik kaydı bulunamadı.']);
        }

        if ($subscription->status !== 'pending') {
            return redirect()->route('home')->with('success', 'Aylık bağışınız zaten işleme alındı.');
        }

        $result = $this->subscriptions->retrieveCheckoutFormResult($token);

        if (($result['status'] ?? '') === 'success' && ($result['subscriptionStatus'] ?? 'ACTIVE') === 'ACTIVE') {
            $subscription->update([
                'status' => 'active',
                'iyzico_sub_ref' => $result['subscriptionReferenceCode'] ?? null,
                'iyzico_customer_ref' => $result['customerReferenceCode'] ?? null,
                'started_at' => now(),
                'next_billing_at' => now()->addMonth(),
            ]);

            if (! empty($result['customerReferenceCode'])) {
                $subscription->donor->update(['iyzico_customer_ref' => $result['customerReferenceCode']]);
            }

            try {
                Mail::to($subscription->donor->email)->queue(new SubscriptionReceiptMail($subscription));
            } catch (\Throwable $e) {
                Log::error('donate.subscription_mail_error', ['message' => $e->getMessage()]);
            }

            return redirect()->route('home')->with(
                'success',
                'Aylık bağışınız başarıyla başlatıldı! Her ay otomatik olarak tahsilat yapılacaktır. Bağış geçmişinizden istediğiniz zaman iptal edebilirsiniz.'
            );
        }

        $subscription->update(['status' => 'failed']);

        return redirect()->route('home')->withErrors([
            'payment' => 'Aylık bağış başlatılamadı: '.self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata'),
        ]);
    }

    private static function getHumanReadableErrorMessage(string $errorMessage, ?string $errorCode = null): string
    {
        // Common Iyzico error codes and their Turkish translations
        $errorTranslations = [
            '10051' => 'Kart limiti yetersiz veya yetersiz bakiye',
            '10052' => 'Kart bilgileri hatalı',
            '10053' => 'Kart sahibi bilgileri hatalı',
            '10054' => 'Kart son kullanma tarihi hatalı',
            '10055' => 'CVC/CVV kodu hatalı',
            '10056' => 'Kart numarası hatalı',
            '10057' => 'Kart türü desteklenmiyor',
            '10058' => '3D Secure doğrulaması başarısız',
            '10059' => 'Ödeme işlemi zaman aşımına uğradı',
            '10060' => 'Banka tarafından işlem reddedildi',
            '10061' => 'Kart bloke edilmiş',
            '10062' => 'Kart çalınmış/kayıp',
            '10063' => 'Kart iptal edilmiş',
            '10064' => 'Kart süresi dolmuş',
            '10065' => 'Kart limiti aşıldı',
            '10066' => 'Günlük işlem limiti aşıldı',
            '10067' => 'Aylık işlem limiti aşıldı',
            '10068' => 'Kart yurtdışı işlemlere kapalı',
            '10069' => 'Kart internet işlemlerine kapalı',
            '10070' => 'Kart telefon işlemlerine kapalı',
        ];

        // If we have a specific error code, use the translation
        if ($errorCode && isset($errorTranslations[$errorCode])) {
            return $errorTranslations[$errorCode];
        }

        // If the error message is already in Turkish, return as is
        if (preg_match('/[çğıöşüÇĞIİÖŞÜ]/', $errorMessage)) {
            return $errorMessage;
        }

        // Common English error patterns and their Turkish translations
        $commonPatterns = [
            '/insufficient.*funds?/i' => 'Yetersiz bakiye',
            '/card.*declined/i' => 'Kart reddedildi',
            '/invalid.*card/i' => 'Geçersiz kart',
            '/expired.*card/i' => 'Süresi dolmuş kart',
            '/invalid.*cvv/i' => 'Geçersiz CVC kodu',
            '/3d.*secure.*failed/i' => '3D Secure doğrulaması başarısız',
            '/timeout/i' => 'İşlem zaman aşımına uğradı',
            '/bank.*rejected/i' => 'Banka tarafından reddedildi',
            '/card.*blocked/i' => 'Kart bloke edilmiş',
            '/stolen.*card/i' => 'Kart çalınmış/kayıp',
            '/cancelled.*card/i' => 'Kart iptal edilmiş',
            '/daily.*limit/i' => 'Günlük işlem limiti aşıldı',
            '/monthly.*limit/i' => 'Aylık işlem limiti aşıldı',
        ];

        foreach ($commonPatterns as $pattern => $translation) {
            if (preg_match($pattern, $errorMessage)) {
                return $translation;
            }
        }

        // If no specific translation found, return the original message
        return $errorMessage;
    }

    public function callback(Request $request): RedirectResponse
    {
        $token = $request->input('token');

        Log::info('donate.callback_started', [
            'token' => $token,
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        if (! $token) {
            Log::warning('donate.callback_no_token', [
                'request_data' => $request->all(),
            ]);

            return redirect()->route('home')->withErrors(['payment' => 'Geçersiz dönüş.']);
        }

        $donation = Donation::where('token', $token)->first();
        if (! $donation) {
            Log::warning('donate.callback_donation_not_found', [
                'token' => $token,
                'request_data' => $request->all(),
            ]);

            return redirect()->route('home')->withErrors(['payment' => 'İşlem bulunamadı.']);
        }

        Log::info('donate.callback_donation_found', [
            'donation_id' => $donation->id,
            'donation_status' => $donation->status,
            'token' => $token,
        ]);

        $result = $this->payments->retrievePaymentResult($token);

        Log::info('donate.callback_payment_result', [
            'donation_id' => $donation->id,
            'result' => $result,
        ]);

        if (($result['status'] ?? '') === 'success') {
            Log::info('donate.callback_payment_success', [
                'donation_id' => $donation->id,
                'result' => $result,
            ]);

            $donation->update([
                'status' => 'success',
                'completed_at' => now(),
                'payment_id' => $result['paymentId'] ?? null,
                'card_last4' => $result['cardLastFour'] ?? null,
                'card_brand' => $result['cardBrand'] ?? null,
                'raw_payload' => $result['raw'] ?? $result,
            ]);

            // mails queued
            Mail::to($donation->email)->queue(new DonationReceiptMail($donation));
            if (config('mail.from.address') && env('ADMIN_EMAIL')) {
                Mail::to(env('ADMIN_EMAIL'))->queue(new AdminDonationNoticeMail($donation, [
                    'ip' => request()->ip(),
                    'ua' => $request->userAgent(),
                ]));
            }

            return redirect()->route('home')->with('success', 'Bağışınız için çok teşekkür ederiz, detaylar e-posta adresinize gönderilmiştir. Bankanız tarafından düzenlenen dekont ya da hesap özetleri alındı belgesi (makbuz) yerine geçmektedir. (Dernekler Kanunu Md. 11)');
        }

        Log::warning('donate.callback_payment_failed', [
            'donation_id' => $donation->id,
            'result' => $result,
            'result_status' => $result['status'] ?? 'undefined',
            'error_message' => $result['errorMessage'] ?? 'No error message',
        ]);

        $donation->update([
            'status' => 'failed',
            'failed_reason' => $result['errorMessage'] ?? 'Ödeme başarısız',
            'raw_payload' => $result['raw'] ?? $result,
        ]);

        return redirect()->route('home')->withErrors(['payment' => 'Ödeme başarısız oldu. Hata nedeni: '.self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata', $result['raw']['errorCode'] ?? null)]);
    }

    public static function parseAmountToMinor(string $input): int
    {
        $normalized = str_replace([' ', '₺'], '', trim($input));
        $normalized = str_replace(['.', ','], ['.', '.'], $normalized);
        if (str_contains($normalized, '.')) {
            [$l, $r] = array_pad(explode('.', $normalized, 2), 2, '0');
            $r = substr(str_pad(preg_replace('/\D/', '', $r), 2, '0'), 0, 2);
            $l = preg_replace('/\D/', '', $l);

            return ((int) $l) * 100 + (int) $r;
        }

        return ((int) preg_replace('/\D/', '', $normalized)) * 100;
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
        $first = implode(' ', $parts);

        return [$first, $last];
    }

    /**
     * Normalizes "05xx...", "5xx...", "90 5xx..." and "+90 5xx..." inputs
     * to E.164 (+905xxxxxxxxx); returns null when it isn't a TR mobile number.
     */
    private static function normalizeTurkishGsm(?string $input): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $input);

        if (str_starts_with($digits, '0090')) {
            $digits = substr($digits, 4);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '+90'.$digits;
        }

        return null;
    }

    private static function parseOnelineCard(string $input): ?array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($input));
        if (! preg_match('/^(?<num>[0-9\s]{12,23})\s+(?<mm>\d{2})\/(?<yy>\d{2,4})\s+(?<cvc>\d{3,4})$/', $normalized, $m)) {
            return null;
        }
        $number = preg_replace('/\D/', '', $m['num']);
        $month = str_pad((string) ((int) $m['mm']), 2, '0', STR_PAD_LEFT);
        $year = $m['yy'];
        if (strlen($year) === 2) {
            $year = '20'.$year;
        }

        return [
            'number' => $number,
            'exp_month' => $month,
            'exp_year' => $year,
            'cvc' => $m['cvc'],
        ];
    }
}
