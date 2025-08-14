<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->string('conversation_id')->index();
            $table->string('payment_id')->nullable();
            $table->string('token')->nullable()->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('TRY');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending')->index();
            $table->string('email');
            $table->string('full_name');
            $table->string('notes', 1000)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_brand', 32)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->timestamps();
            $table->unique('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};



