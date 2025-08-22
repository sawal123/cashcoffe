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
        Schema::create('menu_varians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menus_id')->constrained('menus')->onDelete('cascade');
            $table->string('nama_varian');
            $table->decimal('harga', 10, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->string('gambar')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_varians');
    }
};
