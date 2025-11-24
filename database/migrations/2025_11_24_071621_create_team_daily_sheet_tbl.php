<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_daily_sheet_tbl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('leader_emp_id')->nullable();
            $table->date('sheet_date')->index();
            $table->text('target_text')->nullable(); // single textarea target
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('team_tbl')->onDelete('cascade');
            $table->unique(['team_id', 'sheet_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_daily_sheet_tbl');
    }
};
