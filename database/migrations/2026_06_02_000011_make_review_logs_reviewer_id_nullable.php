<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_review_logs MODIFY `reviewer_id` BIGINT UNSIGNED NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_review_logs MODIFY `reviewer_id` BIGINT UNSIGNED NOT NULL"
        );
    }
};
