<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'problem_description')) {
                $table->text('problem_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'solution_description')) {
                $table->text('solution_description')->nullable()->after('problem_description');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'area_of_application')) {
                $table->string('area_of_application', 255)->nullable()->after('solution_description');
            }
            if (!Schema::hasColumn('sfi_mysql_tb_t_ideas', 'implementation_date')) {
                $table->date('implementation_date')->nullable()->after('area_of_application');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'problem_description')) {
                $table->dropColumn('problem_description');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'solution_description')) {
                $table->dropColumn('solution_description');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'area_of_application')) {
                $table->dropColumn('area_of_application');
            }
            if (Schema::hasColumn('sfi_mysql_tb_t_ideas', 'implementation_date')) {
                $table->dropColumn('implementation_date');
            }
        });
    }
};
