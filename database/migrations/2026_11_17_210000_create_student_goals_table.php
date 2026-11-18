<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_goals')) {
            Schema::create('student_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 255);
                $table->string('title_km', 255)->nullable();
                $table->boolean('is_completed')->default(false);
                $table->string('target_route', 255)->nullable();
                $table->date('goal_date')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'goal_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_goals');
    }
};
