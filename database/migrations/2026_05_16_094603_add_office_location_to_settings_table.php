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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('office_name')->default('Kantor Pusat');
            $table->string('office_latitude')->default('-6.200000');
            $table->string('office_longitude')->default('106.816666');
            $table->integer('office_radius')->default(50); // in meters
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['office_name', 'office_latitude', 'office_longitude', 'office_radius']);
        });
    }
};
