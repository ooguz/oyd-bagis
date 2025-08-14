<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminDonationNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Donation $donation, public array $extra = [])
    {
    }

    public function build(): self
    {
        return $this->subject('Yeni Bağış – '.config('app.name'))
            ->view('emails.admin-donation-notice');
    }
}



