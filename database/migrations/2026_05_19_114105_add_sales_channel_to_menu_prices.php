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
        if (!Schema::hasColumn('menu_prices', 'sales_channel_id')) {
            Schema::table('menu_prices', function (Blueprint $table) {
                $table->foreignId('sales_channel_id')->nullable()->constrained('sales_channels')->onDelete('cascade');
            });
        }
        
        Schema::table('menu_prices', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropForeign(['price_tier_id']);
            
            $table->dropUnique(['menu_id', 'price_tier_id']);
            
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->foreign('price_tier_id')->references('id')->on('price_tiers')->onDelete('cascade');
            
            // Perlu menggunakan statement jika index sudah ada, atau kita asumsikan ini jalan normal
            $table->unique(['menu_id', 'price_tier_id', 'sales_channel_id'], 'menu_prices_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_prices', function (Blueprint $table) {
            $table->dropUnique('menu_prices_unique_index');
            $table->dropForeign(['sales_channel_id']);
            $table->dropColumn('sales_channel_id');
            $table->unique(['menu_id', 'price_tier_id']);
        });
    }
};
