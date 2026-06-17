<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sfi_mysql_tb_t_idea_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idea_id');
            $table->string('name', 255);
            $table->timestamps();

            $table->index('idea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sfi_mysql_tb_t_idea_team_members');
    }
};
