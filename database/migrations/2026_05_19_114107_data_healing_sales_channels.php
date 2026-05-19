<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Healing untuk menu_prices
        DB::table('menu_prices')
            ->whereNull('sales_channel_id')
            ->update(['sales_channel_id' => 1]);

        // 2. Data Healing untuk pesanans
        DB::table('pesanans')
            ->whereNull('sales_channel_id')
            ->update(['sales_channel_id' => 1]);

        // 3. Migrate data dari variant_options.extra_price ke variant_prices (untuk semua price_tiers dan sales_channels)
        // Kita migrasi untuk Tier Reguler (1) dan Dine In (1)
        // Dan untuk tier lain & channel lain, kita akan set nilai yang sama untuk saat ini agar tidak kosong
        
        $tiers = DB::table('price_tiers')->get();
        $channels = DB::table('sales_channels')->get();
        $options = DB::table('variant_options')->get();

        $variantPrices = [];
        foreach ($options as $opt) {
            foreach ($tiers as $tier) {
                foreach ($channels as $channel) {
                    $variantPrices[] = [
                        'variant_option_id' => $opt->id,
                        'price_tier_id' => $tier->id,
                        'sales_channel_id' => $channel->id,
                        'extra_price' => $opt->extra_price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        
        // Batch insert untuk variant_prices (chunked per 1000)
        foreach(array_chunk($variantPrices, 1000) as $chunk) {
            DB::table('variant_prices')->insert($chunk);
        }

        // 4. Update tabel menjadi NOT NULL (hanya untuk MySQL/PGSQL yang support constraint modification)
        // Jika SQLite, update ini membutuhkan recreating table.
        // Asumsi DBMS adalah MySQL based on typical Laravel setup.
        
        try {
            DB::statement('ALTER TABLE menu_prices MODIFY sales_channel_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE variant_prices MODIFY sales_channel_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE pesanans MODIFY sales_channel_id BIGINT UNSIGNED NOT NULL');
        } catch (\Exception $e) {
            // Abaikan error di SQLite (hanya untuk local env)
            // Data sudah diisi, aman meskipun nullable
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot cleanly reverse data healing
    }
};
