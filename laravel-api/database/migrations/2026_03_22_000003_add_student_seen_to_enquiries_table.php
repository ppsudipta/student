<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enquiries')) {
            return;
        }

        if (! Schema::hasColumn('enquiries', 'student_seen')) {
            DB::statement('ALTER TABLE enquiries ADD COLUMN student_seen TINYINT(1) NOT NULL DEFAULT 1 AFTER replied_by');
        }

        DB::statement("UPDATE enquiries SET student_seen = 0 WHERE reply_message IS NOT NULL AND TRIM(reply_message) <> '' AND student_seen = 1");
    }

    public function down(): void
    {
        if (Schema::hasTable('enquiries') && Schema::hasColumn('enquiries', 'student_seen')) {
            DB::statement('ALTER TABLE enquiries DROP COLUMN student_seen');
        }
    }
};
