<?php

namespace App\Services\Payments;

use App\Models\Donor;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class IyzicoSubscriptionService
{
    // Placeholder infrastructure for phase 2
    public function createOrSyncPlan(SubscriptionPlan $plan): void
    {
        // Implement with iyzico subscription APIs
    }

    public function ensureCustomer(Donor $donor): void
    {
        // Map donor to iyzico customer
    }

    public function startSubscription(Donor $donor, SubscriptionPlan $plan): Subscription
    {
        return new Subscription();
    }
}



