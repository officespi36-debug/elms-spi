<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'level')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('level', ['beginner', 'intermediate', 'advanced'])
                    ->nullable()
                    ->after('learning_mode');
            });

            // Safely backfill level from linked subject difficulty where available
            if (Schema::hasTable('subjects') && Schema::hasColumn('courses', 'subject_id') && Schema::hasColumn('subjects', 'difficulty')) {
                $subjectDifficulties = DB::table('subjects')
                    ->whereNotNull('difficulty')
                    ->pluck('difficulty', 'id');

                $courses = DB::table('courses')
                    ->whereNull('level')
                    ->whereNotNull('subject_id')
                    ->select('id', 'subject_id')
                    ->get();

                foreach ($courses as $course) {
                    $raw = strtolower(trim((string) ($subjectDifficulties[$course->subject_id] ?? '')));
                    if (in_array($raw, ['beginner', 'intermediate', 'advanced'], true)) {
                        DB::table('courses')
                            ->where('id', $course->id)
                            ->update(['level' => $raw]);
                    }
                }
            }
        }

        if (!Schema::hasTable('course_wishlists')) {
            Schema::create('course_wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['user_id', 'course_id']);
                $table->index(['course_id']);
            });
        }

        if (!Schema::hasTable('course_reviews')) {
            Schema::create('course_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('body')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
                $table->index(['course_id', 'rating']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('course_wishlists');

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'level')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('level');
            });
        }
    }
};
