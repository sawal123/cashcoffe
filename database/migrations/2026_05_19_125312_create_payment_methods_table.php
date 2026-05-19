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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('nama_metode');
            $table->string('kode_metode')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial payment methods
        $methods = [
            ['nama_metode' => 'Tunai', 'kode_metode' => 'tunai', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'QRIS', 'kode_metode' => 'qris', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'Transfer', 'kode_metode' => 'transfer', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'Kartu', 'kode_metode' => 'kartu', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'ShopeeFood', 'kode_metode' => 'shopeefood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'GoFood', 'kode_metode' => 'gofood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'GrabFood', 'kode_metode' => 'grabfood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_metode' => 'Komplemen', 'kode_metode' => 'komplemen', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('payment_methods')->insert($methods);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
