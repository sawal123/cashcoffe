<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('members')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(100, function ($members): void {
                foreach ($members as $member) {
                    DB::table('members')
                        ->where('id', $member->id)
                        ->update(['phone' => PhoneNumber::member($member->phone)]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
