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
        Schema::create('branch_menu', function (Blueprint $row) {
            $row->id();
            $row->foreignId('branch_id')->constrained()->onDelete('cascade');
            $row->foreignId('menu_id')->constrained()->onDelete('cascade');
            $row->boolean('is_available')->default(true); // Default tersedia
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_menu');
    }
};
