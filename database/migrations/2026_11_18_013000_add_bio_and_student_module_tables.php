<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('bio')->nullable()->after('avatar');
            });
        }

        if (!Schema::hasTable('assignment_submissions')) {
            Schema::create('assignment_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->text('notes')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('file_size', 64)->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->string('status', 32)->default('submitted');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['quiz_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('lesson_notes')) {
            Schema::create('lesson_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
                $table->text('body');
                $table->unsignedInteger('video_seconds')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'lesson_id']);
            });
        }

        if (!Schema::hasTable('notification_reads')) {
            Schema::create('notification_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_key', 100);
                $table->timestamp('read_at')->useCurrent();

                $table->unique(['user_id', 'notification_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('lesson_notes');
        Schema::dropIfExists('assignment_submissions');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('bio');
            });
        }
    }
};
