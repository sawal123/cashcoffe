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
        Schema::table('discounts', function (Blueprint $table) {
            $table->enum('scope', ['global', 'category', 'item'])->default('global')->after('type');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade')->after('scope');
            $table->foreignId('price_tier_id')->nullable()->constrained('price_tiers')->onDelete('set null')->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['price_tier_id']);
            $table->dropColumn(['scope', 'branch_id', 'price_tier_id']);
        });
    }
};
