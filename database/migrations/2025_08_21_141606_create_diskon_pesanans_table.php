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
        Schema::create('diskon_pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanans_id')->constrained('pesanans')->onDelete('cascade');
            $table->foreignId('diskons_id')->constrained('diskons')->onDelete('cascade');
            $table->decimal('nilai_terpakai', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diskon_pesanans');
    }
};
