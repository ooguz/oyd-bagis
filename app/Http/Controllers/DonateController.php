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
        $validated = $request->validate([
            'amount' => ['required', 'string'],
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

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
            $result = $this->payments->createCheckoutForm($donation, [
                'name' => $donor->full_name,
            ], $isThreeD);

            $donation->update([
                'token' => $result['token'] ?? null,
                'raw_payload' => $result,
            ]);

            return view('welcome', [
                'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                'paymentPageUrl' => $result['paymentPageUrl'] ?? null,
                'donation' => $donation,
                'successMessage' => null,
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

    public function callback(Request $request): RedirectResponse
    {
        $token = $request->input('token');
        if (!$token) {
            return redirect()->route('home')->withErrors(['payment' => 'Geçersiz dönüş.']);
        }

        $donation = Donation::where('token', $token)->first();
        if (!$donation) {
            return redirect()->route('home')->withErrors(['payment' => 'İşlem bulunamadı.']);
        }

        $result = $this->payments->retrievePaymentResult($token);

        if (($result['status'] ?? '') === 'success') {
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

            return redirect()->route('home')->with('success', 'Bağışınız için teşekkür ederiz. Makbuz e-postası gönderildi.');
        }

        $donation->update([
            'status' => 'failed',
            'failed_reason' => $result['errorMessage'] ?? 'Ödeme başarısız',
            'raw_payload' => $result['raw'] ?? $result,
        ]);

        return redirect()->route('home')->withErrors(['payment' => 'Ödeme başarısız oldu.']);
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
}



