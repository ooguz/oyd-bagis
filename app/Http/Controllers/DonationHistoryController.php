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

        $link->update(['used_at' => now()]);

        return view('welcome', [
            'historyEmail' => $link->email,
            'donations' => $donations,
        ]);
    }
}



