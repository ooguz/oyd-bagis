<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_magic_link_request_sends_mail(): void
    {
        Mail::fake();
        $this->post('/magic-links/request', ['email' => 'user@example.com'])
            ->assertRedirect('/');
        Mail::assertQueued(MagicLinkMail::class);
    }
}



