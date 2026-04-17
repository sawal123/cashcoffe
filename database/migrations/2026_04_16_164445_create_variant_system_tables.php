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
        // 1. Grup Varian (Suhu, Gula, Toping, dll)
        Schema::create('variant_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_group'); // e.g., 'Suhu', 'Add On'
            $table->enum('selection_type', ['single', 'multiple'])->default('single');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        // 2. Opsi Varian (Hot, Ice, Boba, Less Sugar)
        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_group_id')->constrained('variant_groups')->onDelete('cascade');
            $table->string('nama_opsi');
            $table->decimal('extra_price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 3. Mapping Menu ke Grup Varian (Pivot)
        Schema::create('menu_variant_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('variant_group_id')->constrained('variant_groups')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Mapping Pilihan Varian ke Item Pesanan
        Schema::create('pesanan_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_item_id')->constrained('pesanan_items')->onDelete('cascade');
            $table->foreignId('variant_option_id')->constrained('variant_options')->onDelete('cascade');
            $table->timestamps();
        });

        // 5. Cleanup: Hapus tabel lama yang kaku
        Schema::table('pesanan_items', function (Blueprint $table) {
            if (Schema::hasColumn('pesanan_items', 'varian_id')) {
                $table->dropForeign(['varian_id']); // Hapus constraint dulu ✅
                $table->dropColumn('varian_id');
            }
        });

        Schema::dropIfExists('menu_varians');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('varian_id')->nullable()->after('menus_id');
        });
        
        Schema::dropIfExists('pesanan_item_variants');
        Schema::dropIfExists('menu_variant_group');
        Schema::dropIfExists('variant_options');
        Schema::dropIfExists('variant_groups');
    }
};
