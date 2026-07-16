<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    protected $fillable = [
        'email',
        'full_name',
        'phone',
        'last_donated_at',
        'iyzico_customer_ref',
    ];

    protected $casts = [
        'last_donated_at' => 'datetime',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
