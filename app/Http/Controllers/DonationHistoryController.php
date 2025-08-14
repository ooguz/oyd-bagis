<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\MagicLink;
use Illuminate\Http\Request;

class DonationHistoryController extends Controller
{
    public function index(Request $request)
    {
        $token = (string) $request->query('token');
        $link = MagicLink::where('token', $token)->first();
        if (!$link || !$link->isValid($token)) {
            abort(403, 'Geçersiz veya süresi geçmiş bağlantı.');
        }

        $donations = Donation::where('email', $link->email)
            ->where('created_at', '>=', now()->subMonths(24))
            ->orderByDesc('created_at')
            ->get();

        // Calculate additional statistics
        $successfulDonations = $donations->where('status', 'success');
        $totalAmount = $successfulDonations->sum('amount_minor');
        $averageAmount = $successfulDonations->count() > 0 ? $successfulDonations->avg('amount_minor') : 0;
        $maxAmount = $successfulDonations->count() > 0 ? $successfulDonations->max('amount_minor') : 0;
        $minAmount = $successfulDonations->count() > 0 ? $successfulDonations->min('amount_minor') : 0;

        $link->update(['used_at' => now()]);

        return view('donations.index', [
            'historyEmail' => $link->email,
            'donations' => $donations,
            'totalAmount' => $totalAmount,
            'averageAmount' => $averageAmount,
            'maxAmount' => $maxAmount,
            'minAmount' => $minAmount,
            'successfulCount' => $successfulDonations->count(),
            'failedCount' => $donations->where('status', 'failed')->count(),
            'pendingCount' => $donations->where('status', 'pending')->count(),
        ]);
    }
}



