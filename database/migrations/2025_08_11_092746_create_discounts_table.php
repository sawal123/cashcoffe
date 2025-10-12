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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('nama_diskon');
            $table->enum('jenis_diskon', ['persentase', 'nominal']);
            $table->decimal('nilai_diskon', 10, 2);
            $table->decimal('minimum_transaksi', 10, 2)->nullable(); // Minimum belanja agar diskon berlaku
            $table->decimal('maksimum_diskon', 10, 2)->nullable(); // Batas maksimum potongan (jika persentase)
            $table->string('kode_diskon')->nullable(); // Jika kasir perlu input kode
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->integer('limit')->nullable();
            $table->integer('digunakan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
