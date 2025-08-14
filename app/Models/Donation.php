<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;
    protected $fillable = [
        'donor_id',
        'conversation_id',
        'payment_id',
        'token',
        'amount_minor',
        'currency',
        'status',
        'email',
        'full_name',
        'notes',
        'card_last4',
        'card_brand',
        'raw_payload',
        'completed_at',
        'failed_reason',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}


