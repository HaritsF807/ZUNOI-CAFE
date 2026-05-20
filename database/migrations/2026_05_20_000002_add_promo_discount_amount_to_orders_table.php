<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'promo_discount_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('promo_discount_amount', 12, 2)->default(0)->after('discount_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'promo_discount_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('promo_discount_amount');
            });
        }
    }
};
