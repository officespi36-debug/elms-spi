<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('simulations')) {
            Schema::create('simulations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('title_kh')->nullable();
                $table->enum('skill', ['it', 'en', 'sw', 'tm', 'ag'])->index();
                $table->string('kind')->default('scenario')->index(); // scenario, roleplay, code, calc
                $table->text('instructions')->nullable();
                $table->unsignedInteger('estimated_minutes')->default(15);
                $table->boolean('is_graded')->default(true);
                $table->unsignedInteger('pass_score')->default(70);
                $table->unsignedInteger('max_attempts')->default(3);
                $table->unsignedBigInteger('start_node_id')->nullable();
                $table->boolean('is_published')->default(false)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('simulation_nodes')) {
            Schema::create('simulation_nodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('simulation_id')->constrained('simulations')->cascadeOnDelete();
                $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
                $table->string('title')->nullable();
                $table->longText('body');
                $table->string('media_path')->nullable();
                $table->boolean('is_end')->default(false);
                $table->text('feedback')->nullable();
                $table->timestamps();

                $table->index('simulation_id');
            });
        }

        if (!Schema::hasTable('simulation_choices')) {
            Schema::create('simulation_choices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('node_id')->constrained('simulation_nodes')->cascadeOnDelete();
                $table->text('label');
                $table->unsignedBigInteger('next_node_id')->nullable();
                $table->integer('points')->default(0);
                $table->boolean('is_best')->default(false);
                $table->text('feedback')->nullable();
                $table->timestamps();

                $table->index('node_id');
                $table->index('next_node_id');
            });
        }

        if (!Schema::hasTable('simulation_attempts')) {
            Schema::create('simulation_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('simulation_id')->constrained('simulations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('attempt_no')->default(1);
                $table->unsignedBigInteger('current_node_id')->nullable();
                $table->integer('score')->default(0);
                $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['simulation_id', 'user_id', 'attempt_no']);
                $table->index(['user_id', 'simulation_id']);
            });
        }

        if (!Schema::hasTable('simulation_attempt_steps')) {
            Schema::create('simulation_attempt_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('simulation_attempts')->cascadeOnDelete();
                $table->foreignId('node_id')->constrained('simulation_nodes')->cascadeOnDelete();
                $table->foreignId('choice_id')->constrained('simulation_choices')->cascadeOnDelete();
                $table->integer('points')->default(0);
                $table->timestamp('created_at')->nullable();

                $table->index(['attempt_id', 'node_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_attempt_steps');
        Schema::dropIfExists('simulation_attempts');
        Schema::dropIfExists('simulation_choices');
        Schema::dropIfExists('simulation_nodes');
        Schema::dropIfExists('simulations');
    }
};
