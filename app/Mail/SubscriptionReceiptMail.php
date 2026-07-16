<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function build(): self
    {
        return $this->subject('Aylık bağış aboneliğiniz başlatıldı – Özgür Yazılım Derneği')
            ->view('emails.subscription-receipt');
    }
}
