<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_task_main_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_main_id'); // references task_main_tbl.id
            $table->string('member_emp_id');
            $table->string('client_id')->nullable();
            $table->text('task_description')->nullable();
            $table->text('leader_remark')->nullable();
            $table->enum('status', ['completed', 'not_completed', 'in_progress'])->default('not_completed');
            $table->text('member_remark')->nullable();
            $table->timestamps();

            $table->foreign('task_main_id')->references('id')->on('task_main_tbl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_main_tbl');
    }
};
