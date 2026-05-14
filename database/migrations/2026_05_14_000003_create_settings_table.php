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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('WorkSync');
            $table->string('logo')->default('logo/logow.png');
            $table->string('icon')->default('logo/logow.png');
            $table->timestamps();
        });

        // Insert initial default setting
        DB::table('settings')->insert([
            'app_name' => 'WorkSync CashCoffee',
            'logo'     => 'logo/logow.png',
            'icon'     => 'logo/logow.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
