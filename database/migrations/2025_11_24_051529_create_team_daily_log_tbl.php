<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_daily_log_tbl', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('team_id');

            // These point to employee_tbl but no FK allowed
            $table->string('leader_emp_id');
            $table->string('member_emp_id');

            $table->date('log_date');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Safe FK only to our tables
            $table->foreign('task_id')->references('id')->on('task_tbl');
            $table->foreign('team_id')->references('id')->on('team_tbl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_daily_log_tbl');
    }
};
