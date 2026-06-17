<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sfi_mysql_tb_t_idea_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'reward_percent')) {
                $table->decimal('reward_percent', 5, 2)->nullable()->after('cost_savings');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'voucher_reward')) {
                $table->bigInteger('voucher_reward')->nullable()->after('reward_percent');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'appraisal')) {
                $table->string('appraisal', 255)->nullable()->after('voucher_reward');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'suggestion_factor')) {
                $table->integer('suggestion_factor')->nullable()->after('factor_c');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'remark')) {
                $table->text('remark')->nullable()->after('final_adjusted_reward');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sfi_mysql_tb_t_idea_scores', function (Blueprint $table) {
            if (Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'reward_percent')) {
                $table->dropColumn('reward_percent');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'voucher_reward')) {
                $table->dropColumn('voucher_reward');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'appraisal')) {
                $table->dropColumn('appraisal');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'suggestion_factor')) {
                $table->dropColumn('suggestion_factor');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_idea_scores', 'remark')) {
                $table->dropColumn('remark');
            }
        });
    }
};
