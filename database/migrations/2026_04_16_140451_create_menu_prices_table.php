<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Tabel menu_prices
        Schema::create('menu_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('price_tier_id')->constrained('price_tiers')->onDelete('cascade');
            $table->decimal('harga', 10, 2)->default(0);
            $table->decimal('h_promo', 10, 2)->default(0);
            $table->timestamps();

            // Unique constraint agar satu menu tidak punya dua harga di satu tier yang sama
            $table->unique(['menu_id', 'price_tier_id']);
        });

        // 2. DATA HEALING (HANYA JALANKAN DI DATABASE YANG SUDAH ADA DATA)
        
        // A. Cek apakah sudah ada Tier "Reguler" (ID 1 biasanya)
        $regulerTierId = DB::table('price_tiers')->where('nama_tier', 'Reguler')->value('id');
        if (!$regulerTierId) {
            $regulerTierId = DB::table('price_tiers')->insertGetId([
                'nama_tier' => 'Reguler',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // B. Hubungkan semua Cabang ke Tier Reguler jika price_tier_id masih kosong
        DB::table('branches')->whereNull('price_tier_id')->update(['price_tier_id' => $regulerTierId]);

        // C. Salin harga dari tabel menus ke menu_prices (untuk Tier Reguler)
        $menus = DB::table('menus')->get();
        foreach ($menus as $menu) {
            // Cek apakah sudah ada harganya di menu_prices (kasus rerun migration)
            $exists = DB::table('menu_prices')
                ->where('menu_id', $menu->id)
                ->where('price_tier_id', $regulerTierId)
                ->exists();

            if (!$exists) {
                DB::table('menu_prices')->insert([
                    'menu_id' => $menu->id,
                    'price_tier_id' => $regulerTierId,
                    'harga' => $menu->harga ?? 0,
                    'h_promo' => $menu->h_promo ?? 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_prices');
    }
};
