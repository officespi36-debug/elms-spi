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
        if (!Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
                $table->string('title');
                $table->string('title_kh')->nullable();
                $table->unsignedInteger('position')->default(1);
                $table->timestamps();

                $table->index(['course_id', 'position']);
            });
        }

        if (Schema::hasTable('questions') && !Schema::hasColumn('questions', 'topic_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->foreignId('topic_id')->nullable()->after('quiz_id')->constrained('topics')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('quiz_attempt_answers')) {
            Schema::create('quiz_attempt_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
                $table->json('selected_answer')->nullable();
                $table->boolean('is_correct')->default(false);
                $table->decimal('points_earned', 6, 2)->default(0.00);
                $table->timestamp('answered_at')->nullable();
                $table->timestamps();

                $table->unique(['attempt_id', 'question_id']);
                $table->index(['question_id', 'is_correct']);
            });
        }

        if (!Schema::hasTable('study_plans')) {
            Schema::create('study_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 32)->default('active'); // active, completed, archived
                $table->date('starts_on');
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('study_plan_items')) {
            Schema::create('study_plan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('study_plans')->cascadeOnDelete();
                $table->unsignedSmallInteger('day_index')->default(1);
                $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
                $table->unsignedSmallInteger('minutes')->default(30);
                $table->string('kind', 32)->default('review'); // review, practice
                $table->timestamp('done_at')->nullable();
                $table->foreignId('student_goal_id')->nullable()->constrained('student_goals')->nullOnDelete();
                $table->timestamps();

                $table->index(['plan_id', 'day_index']);
            });
        }

        if (!Schema::hasTable('topic_mastery')) {
            Schema::create('topic_mastery', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
                $table->unsignedInteger('answered_count')->default(0);
                $table->unsignedInteger('correct_count')->default(0);
                $table->decimal('score', 5, 2)->default(0.00);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'topic_id']);
                $table->index(['user_id', 'score']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_mastery');
        Schema::dropIfExists('study_plan_items');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('quiz_attempt_answers');

        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'topic_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['topic_id']);
                $table->dropColumn('topic_id');
            });
        }

        Schema::dropIfExists('topics');
    }
};
