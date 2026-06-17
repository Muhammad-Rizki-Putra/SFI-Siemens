<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            $table->string('revision_checkpoint')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            $table->dropColumn('revision_checkpoint');
        });
    }
};
