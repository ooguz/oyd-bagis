<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'conversation_id' => (string) Str::uuid(),
            'amount_minor' => 10000,
            'currency' => 'TRY',
            'status' => 'pending',
            'email' => 'user@example.com',
            'full_name' => 'Test User',
        ];
    }
}



