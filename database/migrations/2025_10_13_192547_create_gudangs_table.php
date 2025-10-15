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
        Schema::create('gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->string('satuan')->default('pcs');
            $table->decimal('stok', 10, 2)->default(0);
            $table->decimal('harga_satuan', 10, 2)->nullable();
            $table->decimal('minimum_stok', 10, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudangs');
    }
};
