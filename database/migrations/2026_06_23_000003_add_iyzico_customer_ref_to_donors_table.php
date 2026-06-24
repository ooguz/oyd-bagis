<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->string('iyzico_customer_ref')->nullable()->after('last_donated_at');
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn('iyzico_customer_ref');
        });
    }
};
