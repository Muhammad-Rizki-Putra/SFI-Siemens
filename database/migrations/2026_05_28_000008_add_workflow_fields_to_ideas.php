<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'technical_pic')) {
                $table->string('technical_pic', 255)->nullable()->after('current_reviewer_role');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'managerial_decision_summary')) {
                $table->text('managerial_decision_summary')->nullable()->after('technical_pic');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_type')) {
                $table->string('reward_request_type', 100)->nullable()->after('managerial_decision_summary');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_amount')) {
                $table->decimal('reward_request_amount', 12, 2)->nullable()->after('reward_request_type');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_notes')) {
                $table->text('reward_request_notes')->nullable()->after('reward_request_amount');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_processing_status')) {
                $table->string('reward_processing_status', 100)->nullable()->after('reward_request_notes');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_processed_at')) {
                $table->timestamp('reward_processed_at')->nullable()->after('reward_processing_status');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'opw_type')) {
                $table->string('opw_type', 100)->nullable()->after('reward_processed_at');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'opw_notes')) {
                $table->text('opw_notes')->nullable()->after('opw_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'opw_notes')) {
                $table->dropColumn('opw_notes');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'opw_type')) {
                $table->dropColumn('opw_type');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_processed_at')) {
                $table->dropColumn('reward_processed_at');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_processing_status')) {
                $table->dropColumn('reward_processing_status');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_notes')) {
                $table->dropColumn('reward_request_notes');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_amount')) {
                $table->dropColumn('reward_request_amount');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'reward_request_type')) {
                $table->dropColumn('reward_request_type');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'managerial_decision_summary')) {
                $table->dropColumn('managerial_decision_summary');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'technical_pic')) {
                $table->dropColumn('technical_pic');
            }
        });
    }
};
