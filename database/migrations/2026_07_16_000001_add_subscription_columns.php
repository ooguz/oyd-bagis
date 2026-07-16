<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_minor')->default(0)->after('plan_id');
            $table->string('conversation_id')->nullable()->index()->after('amount_minor');
            $table->string('checkout_token')->nullable()->index()->after('iyzico_sub_ref');
            $table->string('iyzico_customer_ref')->nullable()->after('checkout_token');
            $table->timestamp('next_billing_at')->nullable()->after('started_at');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('iyzico_product_ref')->nullable()->after('iyzico_plan_ref');
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->string('iyzico_customer_ref')->nullable()->after('last_donated_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['amount_minor', 'conversation_id', 'checkout_token', 'iyzico_customer_ref', 'next_billing_at']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('iyzico_product_ref');
        });

        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn('iyzico_customer_ref');
        });
    }
};
