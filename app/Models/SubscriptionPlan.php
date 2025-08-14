<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'interval',
        'amount_minor',
        'iyzico_plan_ref',
    ];
}



