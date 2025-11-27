<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_main_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->date('sheet_date')->index();
            $table->string('leader_emp_id')->nullable();
            $table->text('today_target')->nullable();
            $table->text('day_remark')->nullable();
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('team_tbl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_main_tbl');
    }
};
