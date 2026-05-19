<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_channels', function (Blueprint $table) {
            $table->id();
            $table->string('nama_channel');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial data
        DB::table('sales_channels')->insert([
            ['id' => 1, 'nama_channel' => 'Dine In', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_channel' => 'Take Away', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_channel' => 'GrabFood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama_channel' => 'GoFood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
    }
};
