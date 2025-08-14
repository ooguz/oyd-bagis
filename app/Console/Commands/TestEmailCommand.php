<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\DonationReceiptMail;
use App\Mail\AdminDonationNoticeMail;
use App\Models\Donation;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {--donor=} {--admin=}';
    protected $description = 'Test email configuration and send test emails';

    public function handle()
    {
        $this->info('🔍 Testing Email Configuration...');
        
        // Check mail configuration
        $this->info("\n1. Mail Configuration:");
        $this->info("   Driver: " . config('mail.default'));
        $this->info("   From Address: " . config('mail.from.address'));
        $this->info("   From Name: " . config('mail.from.name'));
        
        if (config('mail.default') === 'smtp') {
            $this->info("   SMTP Host: " . config('mail.mailers.smtp.host'));
            $this->info("   SMTP Port: " . config('mail.mailers.smtp.port'));
            $this->info("   SMTP Username: " . config('mail.mailers.smtp.username'));
        }
        
        // Check admin email
        $adminEmail = env('ADMIN_EMAIL');
        if ($adminEmail) {
            $this->info("   Admin Email: " . $adminEmail);
        } else {
            $this->warn("   ❌ ADMIN_EMAIL not set in .env");
        }
        
        // Test donor email
        $donorEmail = $this->option('donor') ?: 'test@example.com';
        $this->info("\n2. Testing Donor Email to: " . $donorEmail);
        
        try {
            // Create a mock donation for testing
            $mockDonation = new Donation([
                'id' => 999,
                'full_name' => 'Test Kullanıcı',
                'email' => $donorEmail,
                'amount_minor' => 5000, // 50 TL
                'conversation_id' => 'test-' . uniqid(),
                'payment_id' => 'test-payment-' . uniqid(),
                'card_last4' => '1234',
                'card_brand' => 'Visa',
                'notes' => 'Bu bir test bağışıdır.',
                'created_at' => now(),
                'completed_at' => now(),
            ]);
            
            Mail::to($donorEmail)->send(new DonationReceiptMail($mockDonation));
            $this->info("   ✅ Donor email sent successfully");
        } catch (\Exception $e) {
            $this->error("   ❌ Donor email failed: " . $e->getMessage());
        }
        
        // Test admin email
        if ($adminEmail) {
            $this->info("\n3. Testing Admin Email to: " . $adminEmail);
            
            try {
                Mail::to($adminEmail)->send(new AdminDonationNoticeMail($mockDonation, [
                    'ip' => '127.0.0.1',
                    'ua' => 'Test Browser',
                ]));
                $this->info("   ✅ Admin email sent successfully");
            } catch (\Exception $e) {
                $this->error("   ❌ Admin email failed: " . $e->getMessage());
            }
        }
        
        // Check mail logs
        $this->info("\n4. Mail Logs:");
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lastLines = shell_exec("tail -10 $logPath 2>/dev/null | grep -i mail");
            if ($lastLines) {
                $this->info("   Recent mail-related log entries:");
                $this->line($lastLines);
            } else {
                $this->info("   No recent mail-related log entries found");
            }
        }
        
        $this->info("\n✅ Email test completed!");
        $this->info("\n📋 Next Steps:");
        $this->info("1. Check your email inbox for test emails");
        $this->info("2. If emails not received, check your mail configuration");
        $this->info("3. Verify SMTP settings or switch to a different mail driver");
        $this->info("4. Check server logs for mail errors");
    }
}
