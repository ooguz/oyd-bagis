<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Aylık 100 TL', 'interval' => 'monthly', 'amount_minor' => 10000],
            ['name' => 'Aylık 250 TL', 'interval' => 'monthly', 'amount_minor' => 25000],
            ['name' => 'Yıllık 1200 TL', 'interval' => 'yearly', 'amount_minor' => 120000],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name'], 'interval' => $plan['interval']],
                ['amount_minor' => $plan['amount_minor']]
            );
        }
    }
}



