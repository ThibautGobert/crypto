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
        Schema::create('crypto_trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('crypto_type');
            $table->decimal('price', 18, 8);
            $table->decimal('quantity', 18, 8);
            $table->boolean('maker')->default(false);
            $table->timestamp('timestamp');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_trades');
    }
};
