<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members_tbl', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('team_id');
            $table->string('emp_id'); // No foreign key (existing table incompatible)

            $table->boolean('is_leader')->default(false);

            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('team_tbl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members_tbl');
    }
};
