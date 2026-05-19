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
        // 1. Add payment_method_id as a nullable column first
        Schema::table('pesanans', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('metode_pembayaran')->constrained('payment_methods')->onDelete('set null');
        });

        // 2. Perform Data Healing
        $orders = DB::table('pesanans')->whereNotNull('metode_pembayaran')->get();
        
        foreach ($orders as $order) {
            $metode = strtolower(trim($order->metode_pembayaran));
            if ($metode === '') {
                continue;
            }

            // Find matching payment method by kode_metode
            $paymentMethod = DB::table('payment_methods')->where('kode_metode', $metode)->first();
            if ($paymentMethod) {
                DB::table('pesanans')
                    ->where('id', $order->id)
                    ->update(['payment_method_id' => $paymentMethod->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanans', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
