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
        Schema::create('riwayat_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->string('kode')->nullable();
            $table->decimal('qty', 10, 2);
            $table->decimal('qty_before', 10, 2)->nullable();
            $table->decimal('qty_after', 10, 2)->nullable();
            $table->enum('tipe', ['in', 'out'])->default('in');
            $table->string('keterangan')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_stocks');
    }
};
