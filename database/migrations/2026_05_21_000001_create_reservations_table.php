<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->unsignedSmallInteger('num_guests')->default(1);
            $table->text('notes')->nullable();
            // Pre-order items stored as JSON array: [{id, name, price, quantity, notes}]
            $table->json('preorder_items')->nullable();
            // order_id is set after preorder_items are converted to a real Order
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
