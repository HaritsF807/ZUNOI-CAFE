<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('discount_type', ['percentage', 'nominal']);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_purchase', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('voucher_code')->nullable()->after('notes');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'discount_amount']);
        });
        Schema::dropIfExists('vouchers');
    }
};
