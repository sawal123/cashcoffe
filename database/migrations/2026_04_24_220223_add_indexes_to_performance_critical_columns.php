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
        Schema::table('menus', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('nama_menu');
        });

        Schema::table('pesanan_items', function (Blueprint $table) {
            $table->index('menus_id');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->index('kode_diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['nama_menu']);
        });

        Schema::table('pesanan_items', function (Blueprint $table) {
            $table->dropIndex(['menus_id']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropIndex(['kode_diskon']);
        });
    }
};
