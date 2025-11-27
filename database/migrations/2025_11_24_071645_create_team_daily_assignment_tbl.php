<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_daily_assignment_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sheet_id');
            $table->string('member_emp_id');      // employee_tbl.emp_id
            $table->string('client_id')->nullable(); // m_client_tbl.client_id
            $table->text('task_description')->nullable(); // leader described task
            $table->text('leader_remark')->nullable();
            // status as enum (completed, not_completed, in_progress)
            $table->enum('status', ['completed', 'not_completed', 'in_progress'])->default('not_completed');
            // remark when not completed or in progress (member's remark)
            $table->text('member_remark')->nullable();
            $table->boolean('is_submitted')->default(false); // whether member submitted status/remark
            $table->timestamps();

            $table->foreign('sheet_id')->references('id')->on('team_daily_sheet_tbl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_daily_assignment_tbl');
    }
};
