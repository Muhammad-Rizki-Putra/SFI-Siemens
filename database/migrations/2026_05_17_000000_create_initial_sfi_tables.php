<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create Departments
        Schema::create('sfi_mysql_tb_r_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Rename and modify users table
        if (Schema::hasTable('users') && !Schema::hasTable('sfi_mysql_tb_r_users')) {
            Schema::rename('users', 'sfi_mysql_tb_r_users');
        }
        
        Schema::table('sfi_mysql_tb_r_users', function (Blueprint $table) {
            if (!Schema::hasColumn('sfi_mysql_tb_r_users', 'entra_id')) {
                $table->string('entra_id')->nullable();
            }
            if (!Schema::hasColumn('sfi_mysql_tb_r_users', 'supervisor_email')) {
                $table->string('supervisor_email')->nullable();
            }
            if (!Schema::hasColumn('sfi_mysql_tb_r_users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable();
            }
            if (!Schema::hasColumn('sfi_mysql_tb_r_users', 'role')) {
                $table->string('role')->default('user');
            }
        });

        // Create Ideas table
        Schema::create('sfi_mysql_tb_t_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('submission_code')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('type_of_improvement')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->string('title')->nullable();
            $table->text('description');
            $table->enum('status', ['Draft','Submitted','SPS Review','Technical Review','Managerial Review','Reward Processing','Implemented','Rejected'])->default('Draft');
            $table->integer('completion_percentage')->default(0);
            $table->string('current_reviewer_role')->nullable();
            $table->timestamps();
        });

        // Create Idea Scores table
        Schema::create('sfi_mysql_tb_t_idea_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idea_id');
            $table->string('category')->nullable();
            $table->decimal('cost_savings', 15, 2)->nullable();
            $table->integer('factor_a1')->nullable();
            $table->integer('factor_a2')->nullable();
            $table->integer('factor_a3')->nullable();
            $table->integer('factor_b')->nullable();
            $table->integer('implementation_factor')->nullable();
            $table->integer('factor_c')->nullable();
            $table->integer('total_points')->nullable();
            $table->decimal('calculated_reward', 15, 2)->nullable();
            $table->decimal('final_adjusted_reward', 15, 2)->nullable();
            $table->timestamps();
        });

        // Create Review Logs table
        Schema::create('sfi_mysql_tb_t_review_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idea_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->enum('action', ['Approved','Rejected']);
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sfi_mysql_tb_t_review_logs');
        Schema::dropIfExists('sfi_mysql_tb_t_idea_scores');
        Schema::dropIfExists('sfi_mysql_tb_t_ideas');
        
        if (Schema::hasTable('sfi_mysql_tb_r_users')) {
            Schema::table('sfi_mysql_tb_r_users', function (Blueprint $table) {
                if (Schema::hasColumn('sfi_mysql_tb_r_users', 'entra_id')) {
                    $table->dropColumn('entra_id');
                }
                if (Schema::hasColumn('sfi_mysql_tb_r_users', 'supervisor_email')) {
                    $table->dropColumn('supervisor_email');
                }
                if (Schema::hasColumn('sfi_mysql_tb_r_users', 'department_id')) {
                    $table->dropColumn('department_id');
                }
                if (Schema::hasColumn('sfi_mysql_tb_r_users', 'role')) {
                    $table->dropColumn('role');
                }
            });
            if (!Schema::hasTable('users')) {
                Schema::rename('sfi_mysql_tb_r_users', 'users');
            }
        }
        
        Schema::dropIfExists('sfi_mysql_tb_r_departments');
    }
};
