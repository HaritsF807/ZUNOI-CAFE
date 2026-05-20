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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['bundling', 'buy_get']);
            
            // Item pembelian pertama (atau item bundling A)
            $table->foreignId('buy_product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->integer('buy_quantity')->default(1);
            
            // Item bundling B (untuk type = bundling)
            $table->foreignId('bundling_product_id')->nullable()->constrained('products')->onDelete('cascade');
            
            // Item reward (untuk type = buy_get)
            $table->foreignId('get_product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->integer('get_quantity')->default(1);
            
            // Jenis potongan
            $table->enum('discount_type', ['percentage', 'nominal', 'free']);
            $table->decimal('discount_value', 12, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
