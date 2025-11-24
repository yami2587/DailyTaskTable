<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('today_targets_tbl', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('task_id');

            $table->string('title');
            $table->text('remark')->nullable();
            $table->boolean('is_done')->default(false);

            $table->date('target_date');

            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('task_tbl')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('today_targets_tbl');
    }
};
