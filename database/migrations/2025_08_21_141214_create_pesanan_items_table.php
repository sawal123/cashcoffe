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
        Schema::create('pesanan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanans_id')->constrained('pesanans')->onDelete('cascade');
            $table->foreignId('menus_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('varian_id')->nullable()->constrained('menu_varians')->onDelete('set null');
            $table->integer('qty')->default(1);
            $table->decimal('harga_satuan', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('catatan_item')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan_items');
    }
};
