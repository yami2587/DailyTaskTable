<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_tbl', function (Blueprint $table) {
            $table->id();

            $table->string('task_title');
            $table->text('task_description')->nullable();

            // These reference existing tables but WITHOUT foreign keys
            $table->string('client_id')->nullable(); 
            $table->unsignedBigInteger('assigned_team_id')->nullable();
            $table->string('assigned_member_id')->nullable();

            $table->enum('task_type', ['main', 'other'])->default('main');
            $table->enum('status', ['pending','in-progress','completed'])->default('pending');
            $table->date('due_date')->nullable();

            $table->timestamps();

            $table->foreign('assigned_team_id')->references('id')->on('team_tbl')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_tbl');
    }
};
