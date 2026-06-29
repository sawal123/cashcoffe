<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('ai_chat_histories', function (Blueprint $table) {
                $table->dropUnique('ai_chat_histories_user_id_unique');
            });
        } catch (\Throwable) {
            // Fresh databases may already be created without the old unique index.
        }

        if (! Schema::hasColumn('ai_chat_histories', 'title')) {
            Schema::table('ai_chat_histories', function (Blueprint $table) {
                $table->string('title')->default('Chat baru')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_chat_histories', 'title')) {
            Schema::table('ai_chat_histories', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
