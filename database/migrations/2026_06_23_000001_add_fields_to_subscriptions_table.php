<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_minor')->default(0)->after('plan_id');
            $table->string('iyzico_customer_ref')->nullable()->after('iyzico_sub_ref');
            $table->timestamp('next_billing_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['amount_minor', 'iyzico_customer_ref', 'next_billing_at']);
        });
    }
};
