<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_review_logs MODIFY `action` ENUM('Approved','Rejected','Implemented','Draft','Revision Requested','Closed','Technical Review','Managerial Review','Reward Processing','SPS Review', 'Resubmitted') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_review_logs MODIFY `action` ENUM('Approved','Rejected','Implemented','Draft','Revision Requested','Closed','Technical Review','Managerial Review','Reward Processing', 'SPS Review') NOT NULL"
        );
    }
};
