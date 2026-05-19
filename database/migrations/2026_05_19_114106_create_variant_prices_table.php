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
        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_option_id')->constrained('variant_options')->onDelete('cascade');
            $table->foreignId('price_tier_id')->constrained('price_tiers')->onDelete('cascade');
            $table->foreignId('sales_channel_id')->nullable()->constrained('sales_channels')->onDelete('cascade');
            $table->decimal('extra_price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['variant_option_id', 'price_tier_id', 'sales_channel_id'], 'variant_prices_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_prices');
    }
};
