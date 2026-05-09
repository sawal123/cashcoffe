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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('kode_aset')->unique();
            $table->string('nama_aset');
            $table->enum('kategori', ['Elektronik', 'Furniture', 'Peralatan Dapur', 'IT']);
            $table->enum('kondisi', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Dalam Perbaikan'])->default('Baik');
            $table->date('tanggal_pembelian');
            $table->bigInteger('harga_beli');
            $table->text('keterangan')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
