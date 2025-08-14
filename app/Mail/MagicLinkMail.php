<?php

namespace App\Mail;

use App\Models\MagicLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MagicLink $link)
    {
    }

    public function build(): self
    {
        return $this->subject('Bağış geçmişiniz – Özgür Yazılım Derneği')
            ->view('emails.magic-link');
    }
}



