<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_chat_histories')) {
            return;
        }

        if (! Schema::hasColumn('ai_chat_histories', 'title')) {
            Schema::table('ai_chat_histories', function ($table) {
                $table->string('title')->default('Chat baru')->after('user_id');
            });
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $indexes = DB::select('SHOW INDEX FROM ai_chat_histories');
            $hasPlainUserIndex = collect($indexes)->contains(function ($index) {
                return (int) $index->Non_unique === 1
                    && $index->Column_name === 'user_id';
            });

            if (! $hasPlainUserIndex) {
                DB::statement('ALTER TABLE ai_chat_histories ADD INDEX `ai_chat_histories_user_id_index` (`user_id`)');
                $indexes = DB::select('SHOW INDEX FROM ai_chat_histories');
            }

            foreach ($indexes as $index) {
                if ((int) $index->Non_unique === 0
                    && $index->Key_name !== 'PRIMARY'
                    && $index->Column_name === 'user_id') {
                    DB::statement("ALTER TABLE ai_chat_histories DROP INDEX `{$index->Key_name}`");
                }
            }

            return;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('ai_chat_histories')");

            foreach ($indexes as $index) {
                if ((int) ($index->unique ?? 0) !== 1) {
                    continue;
                }

                $columns = DB::select("PRAGMA index_info('{$index->name}')");
                if (count($columns) === 1 && ($columns[0]->name ?? null) === 'user_id') {
                    DB::statement("DROP INDEX {$index->name}");
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally not restoring the old unique index because chat sessions are now multi-row per user.
    }
};
