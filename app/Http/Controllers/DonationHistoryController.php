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
    // Magic links are single-use; once one is consumed the dashboard (and the
    // subscription management actions) stay available through this session key.
    private const SESSION_EMAIL = 'donation_history_email';

    private const SESSION_EXPIRES = 'donation_history_expires_at';

    public function __construct(
        private readonly IyzicoSubscriptionService $subscriptions
    ) {}

    public function index(Request $request)
    {
        $token = (string) $request->query('token');
        $email = null;

        if ($token !== '') {
            $link = MagicLink::where('token', $token)->first();
            if ($link && $link->isValid($token)) {
                $link->update(['used_at' => now()]);
                $email = $link->email;
                $request->session()->put(self::SESSION_EMAIL, $email);
                $request->session()->put(self::SESSION_EXPIRES, now()->addMinutes(30)->timestamp);
            }
        }

        $email ??= $this->sessionEmail($request);
        if (! $email) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        $donations = Donation::where('email', $email)
            ->where('created_at', '>=', now()->subMonths(24))
            ->orderByDesc('created_at')
            ->get();

        // Calculate additional statistics
        $successfulDonations = $donations->where('status', 'success');
        $totalAmount = $successfulDonations->sum('amount_minor');
        $averageAmount = $successfulDonations->count() > 0 ? $successfulDonations->avg('amount_minor') : 0;
        $maxAmount = $successfulDonations->count() > 0 ? $successfulDonations->max('amount_minor') : 0;
        $minAmount = $successfulDonations->count() > 0 ? $successfulDonations->min('amount_minor') : 0;

        $donor = Donor::where('email', $email)->first();
        $subscriptions = $donor
            ? $donor->subscriptions()
                ->whereIn('status', ['active', 'payment_failed', 'cancelled'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('donations.index', [
            'historyEmail' => $email,
            'donations' => $donations,
            'totalAmount' => $totalAmount,
            'averageAmount' => $averageAmount,
            'maxAmount' => $maxAmount,
            'minAmount' => $minAmount,
            'successfulCount' => $successfulDonations->count(),
            'failedCount' => $donations->where('status', 'failed')->count(),
            'pendingCount' => $donations->where('status', 'pending')->count(),
            'subscriptions' => $subscriptions,
        ]);
    }

    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        $this->authorizeSubscription($request, $subscription);

        if (! $subscription->isActive()) {
            return redirect()->route('donations.index')
                ->withErrors(['subscription' => 'Bu abonelik zaten aktif değil.']);
        }

        $result = $this->subscriptions->cancelSubscription($subscription);

        if (($result['status'] ?? '') !== 'success') {
            return redirect()->route('donations.index')
                ->withErrors(['subscription' => 'Abonelik iptal edilemedi. Lütfen daha sonra tekrar deneyin.']);
        }

        $subscription->update([
            'status' => 'cancelled',
            'canceled_at' => now(),
        ]);

        Log::info('donations.subscription_cancelled', [
            'subscription_id' => $subscription->id,
            'donor_email' => $subscription->donor->email,
        ]);

        return redirect()->route('donations.index')
            ->with('success', 'Aylık bağışınız iptal edildi. Bundan sonra tahsilat yapılmayacaktır.');
    }

    public function cardUpdateInit(Request $request, Subscription $subscription)
    {
        $this->authorizeSubscription($request, $subscription);

        $result = $this->subscriptions->createCardUpdateForm(
            $subscription,
            route('donations.card-update.callback')
        );

        if (($result['status'] ?? '') !== 'success' || empty($result['checkoutFormContent'])) {
            return redirect()->route('donations.index')
                ->withErrors(['subscription' => 'Kart güncelleme formu açılamadı: '.($result['errorMessage'] ?? 'Bilinmeyen hata')]);
        }

        return view('donations.card-update', [
            'checkoutFormContent' => $result['checkoutFormContent'],
            'subscription' => $subscription,
        ]);
    }

    public function cardUpdateCallback(Request $request)
    {
        Log::info('donations.card_update_callback', ['request_data' => $request->all()]);

        // iyzico POSTs here cross-site (no session cookie), so land on the public
        // home page instead of the session-gated dashboard.
        return redirect()->route('home')
            ->with('success', 'Kart güncelleme işlemi tamamlandı.');
    }

    private function sessionEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        $expiresAt = $request->session()->get(self::SESSION_EXPIRES);

        if (! $email || ! $expiresAt || now()->timestamp > $expiresAt) {
            return null;
        }

        return $email;
    }

    private function authorizeSubscription(Request $request, Subscription $subscription): void
    {
        $email = $this->sessionEmail($request);
        if (! $email) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        $donor = Donor::where('email', $email)->first();
        if (! $donor || $subscription->donor_id !== $donor->id) {
            abort(403, 'Bu aboneliğe erişim izniniz yok.');
        }
    }
}
