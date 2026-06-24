<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'donor_id',
        'plan_id',
        'amount_minor',
        'status',
        'iyzico_sub_ref',
        'iyzico_customer_ref',
        'started_at',
        'canceled_at',
        'next_billing_at',
    ];

    protected $casts = [
        'started_at'      => 'datetime',
        'canceled_at'     => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaymentFailed(): bool
    {
        return $this->status === 'payment_failed';
    }
}
