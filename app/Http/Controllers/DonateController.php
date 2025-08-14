<?php

namespace App\Http\Controllers;

use App\Mail\AdminDonationNoticeMail;
use App\Mail\DonationReceiptMail;
use App\Models\Donation;
use App\Models\Donor;
use App\Services\Payments\IyzicoPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DonateController extends Controller
{
    public function __construct(private readonly IyzicoPaymentService $payments)
    {
    }

    public function start(Request $request)
    {
        $rules = [
            'amount' => ['required', 'string'],
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
        if (config('payments.flow', 'checkout') === 'direct') {
            $rules['card_oneline'] = ['required', 'string', 'min:10'];
        }
        $validated = $request->validate($rules);

        $amountMinor = self::parseAmountToMinor($validated['amount']);
        if ($amountMinor < 100) {
            return back()->withErrors(['amount' => 'Minimum 1 TL tutar giriniz.'])->withInput();
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
                if (!$card) {
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
                return back()->withErrors(['payment' => 'Ödeme başarısız oldu. Hata nedeni: ' . self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata', $result['raw']['errorCode'] ?? null)])->withInput();
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
        
        if (!$token) {
            Log::warning('donate.callback_no_token', [
                'request_data' => $request->all(),
            ]);
            return redirect()->route('home')->withErrors(['payment' => 'Geçersiz dönüş.']);
        }

        $donation = Donation::where('token', $token)->first();
        if (!$donation) {
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

        return redirect()->route('home')->withErrors(['payment' => 'Ödeme başarısız oldu. Hata nedeni: ' . self::getHumanReadableErrorMessage($result['errorMessage'] ?? 'Bilinmeyen hata', $result['raw']['errorCode'] ?? null)]);
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
    private static function parseOnelineCard(string $input): ?array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($input));
        if (!preg_match('/^(?<num>[0-9\s]{12,23})\s+(?<mm>\d{2})\/(?<yy>\d{2,4})\s+(?<cvc>\d{3,4})$/', $normalized, $m)) {
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



