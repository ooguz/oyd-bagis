<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\MagicLink;
use App\Models\Subscription;
use App\Services\Payments\IyzicoSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DonationHistoryController extends Controller
{
    public function __construct(
        private readonly IyzicoSubscriptionService $subscriptions
    ) {
    }

    public function index(Request $request)
    {
        $token = (string) $request->query('token');
        $link  = MagicLink::where('token', $token)->first();
        if (!$link || !$link->isValid($token)) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        $donations = Donation::where('email', $link->email)
            ->where('created_at', '>=', now()->subMonths(24))
            ->orderByDesc('created_at')
            ->get();

        // Calculate additional statistics
        $successfulDonations = $donations->where('status', 'success');
        $totalAmount    = $successfulDonations->sum('amount_minor');
        $averageAmount  = $successfulDonations->count() > 0 ? $successfulDonations->avg('amount_minor') : 0;
        $maxAmount      = $successfulDonations->count() > 0 ? $successfulDonations->max('amount_minor') : 0;
        $minAmount      = $successfulDonations->count() > 0 ? $successfulDonations->min('amount_minor') : 0;

        // Fetch subscriptions for this donor
        $donor = Donor::where('email', $link->email)->first();
        $activeSubscriptions = collect();
        if ($donor) {
            $activeSubscriptions = $donor->subscriptions()
                ->with('plan')
                ->orderByDesc('created_at')
                ->get();
        }

        $link->update(['used_at' => now()]);

        return view('donations.index', [
            'historyEmail'        => $link->email,
            'magicLinkToken'      => $token,
            'donations'           => $donations,
            'totalAmount'         => $totalAmount,
            'averageAmount'       => $averageAmount,
            'maxAmount'           => $maxAmount,
            'minAmount'           => $minAmount,
            'successfulCount'     => $successfulDonations->count(),
            'failedCount'         => $donations->where('status', 'failed')->count(),
            'pendingCount'        => $donations->where('status', 'pending')->count(),
            'activeSubscriptions' => $activeSubscriptions,
        ]);
    }

    /**
     * Cancel a subscription. Requires the magic link token in the request body.
     */
    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        // Validate magic link token
        $token = (string) $request->input('magic_token');
        $link  = MagicLink::where('token', $token)->first();

        if (!$link || !$link->isValid($token)) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        // Ensure the subscription belongs to this donor
        $donor = Donor::where('email', $link->email)->first();
        if (!$donor || $subscription->donor_id !== $donor->id) {
            abort(403, 'Bu aboneliğe erişim izniniz yok.');
        }

        if (!$subscription->isActive()) {
            return redirect()->to(route('donations.index', ['token' => $token]))
                ->withErrors(['subscription' => 'Bu abonelik zaten aktif değil.']);
        }

        try {
            $success = $this->subscriptions->cancelSubscription($subscription);

            if ($success) {
                $subscription->update([
                    'status'      => 'cancelled',
                    'canceled_at' => now(),
                ]);

                Log::info('donations.subscription_cancelled', [
                    'subscription_id' => $subscription->id,
                    'donor_email'     => $link->email,
                ]);

                return redirect()->to(route('donations.index', ['token' => $token]))
                    ->with('success', 'Aylık bağışınız başarıyla iptal edildi. Mevcut dönem sonunda tahsilat yapılmayacaktır.');
            }

            return redirect()->to(route('donations.index', ['token' => $token]))
                ->withErrors(['subscription' => 'Abonelik iptal edilemedi. Lütfen daha sonra tekrar deneyin.']);
        } catch (\Throwable $e) {
            Log::error('donations.subscription_cancel_error', [
                'subscription_id' => $subscription->id,
                'message'         => $e->getMessage(),
            ]);

            return redirect()->to(route('donations.index', ['token' => $token]))
                ->withErrors(['subscription' => 'Abonelik iptal edilirken bir hata oluştu.']);
        }
    }

    /**
     * Initialize the card update checkout form for a subscription.
     */
    public function cardUpdateInit(Request $request, Subscription $subscription)
    {
        $token = (string) $request->input('magic_token');
        $link  = MagicLink::where('token', $token)->first();

        if (!$link || !$link->isValid($token)) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        $donor = Donor::where('email', $link->email)->first();
        if (!$donor || $subscription->donor_id !== $donor->id) {
            abort(403, 'Bu aboneliğe erişim izniniz yok.');
        }

        try {
            $callbackUrl = route('donations.card-update.callback', ['token' => $token]);
            $result      = $this->subscriptions->initializeCardUpdate($subscription, $callbackUrl);

            if (($result['status'] ?? '') !== 'success') {
                return redirect()->to(route('donations.index', ['token' => $token]))
                    ->withErrors(['subscription' => 'Kart güncelleme formu açılamadı: ' . ($result['errorMessage'] ?? 'Bilinmeyen hata')]);
            }

            // Return view with checkout form content
            return view('donations.card-update', [
                'checkoutFormContent' => $result['checkoutFormContent'] ?? null,
                'subscription'        => $subscription,
                'magicLinkToken'      => $token,
            ]);
        } catch (\Throwable $e) {
            Log::error('donations.card_update_error', [
                'subscription_id' => $subscription->id,
                'message'         => $e->getMessage(),
            ]);

            return redirect()->to(route('donations.index', ['token' => $token]))
                ->withErrors(['subscription' => 'Kart güncelleme başlatılırken bir hata oluştu.']);
        }
    }

    /**
     * Callback after the card update checkout form completes.
     */
    public function cardUpdateCallback(Request $request)
    {
        $token = (string) $request->query('token');
        Log::info('donations.card_update_callback', [
            'token'        => $token,
            'request_data' => $request->all(),
        ]);

        return redirect()->to(route('donations.index', ['token' => $token]))
            ->with('success', 'Kart bilgileriniz başarıyla güncellendi.');
    }
}
