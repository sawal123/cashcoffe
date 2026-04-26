<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel ini menyimpan komposisi bahan baku tambahan per opsi varian.
     * Contoh: Varian "Large" membutuhkan +10g kopi dan +50ml susu.
     */
    public function up(): void
    {
        Schema::dropIfExists('variant_option_ingredients');

        Schema::create('variant_option_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_option_id')
                  ->constrained('variant_options')
                  ->onDelete('cascade');
            $table->foreignId('ingredient_id')
                  ->constrained('ingredients')
                  ->onDelete('cascade');
            $table->decimal('qty', 10, 2)->comment('Jumlah bahan tambahan per unit pesanan');
            $table->timestamps();

            // Satu opsi varian tidak boleh punya entri bahan yang sama dua kali
            $table->unique(['variant_option_id', 'ingredient_id'], 'voi_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_option_ingredients');
    }
};
