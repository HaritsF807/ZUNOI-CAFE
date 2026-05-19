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
        // Drop foreign key first to allow column modification
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
        });

        // Modify the column to be nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->change();
        });

        // Re-add foreign key constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
        });
    }
};
