<?php

namespace App\Http\Controllers;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicLinkController extends Controller
{
    public function requestLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $token = Str::random(40);
        $link = MagicLink::create([
            'email' => $request->input('email'),
            'token' => $token,
            'expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($link->email)->queue(new MagicLinkMail($link));

        return back()->with('success', 'Giriş bağlantısı e-posta adresinize gönderildi.');
    }
}



