<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sfi_mysql_tb_t_idea_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idea_id');
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->boolean('is_video')->default(false);
            $table->boolean('is_compressed')->default(false);
            $table->decimal('compression_ratio', 5, 2)->nullable();
            $table->timestamps();

            $table->index('idea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sfi_mysql_tb_t_idea_attachments');
    }
};
