<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'donor_id',
        'plan_id',
        'status',
        'iyzico_sub_ref',
        'started_at',
        'canceled_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}



