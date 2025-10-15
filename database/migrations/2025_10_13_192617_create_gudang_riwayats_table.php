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
        Schema::create('gudang_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->onDelete('cascade');
            $table->enum('tipe', ['masuk', 'keluar']); // apakah stok masuk atau keluar
            $table->decimal('jumlah', 10, 2);
            $table->decimal('stok_sebelum', 10, 2);
            $table->decimal('stok_sesudah', 10, 2);
            $table->decimal('harga_satuan', 10, 2)->nullable();
            $table->decimal('total_harga', 15, 2)->nullable();
            $table->string('keterangan')->nullable(); // misal "restock" atau "dipakai menu kopi latte"
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // siapa yang melakukan perubahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_riwayats');
    }
};
