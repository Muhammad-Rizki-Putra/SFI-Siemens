<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_ideas MODIFY `status` ENUM('Draft','Submitted','SPS Review','Revision Requested','Technical Review','Managerial Review','Reward Processing','Implemented','Rejected','Closed') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sfi_mysql_tb_t_ideas MODIFY `status` ENUM('Draft','Submitted','SPS Review','Technical Review','Managerial Review','Reward Processing','Implemented','Rejected') NOT NULL"
        );
    }
};
