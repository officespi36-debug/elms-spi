<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createWithFkFallback(
            'learning_paths',
            function (Blueprint $table) {
                $table->id();
                $table->string('slug', 120)->unique();
                $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
                $table->string('title', 200);
                $table->string('title_kh', 200)->nullable();
                $table->text('description')->nullable();
                $table->text('description_kh')->nullable();
                $table->unsignedTinyInteger('target_months')->default(6);
                $table->boolean('is_published')->default(true)->index();
                $table->timestamps();
            },
            function (Blueprint $table) {
                $table->id();
                $table->string('slug', 120)->unique();
                $table->unsignedBigInteger('major_id')->nullable()->index();
                $table->string('title', 200);
                $table->string('title_kh', 200)->nullable();
                $table->text('description')->nullable();
                $table->text('description_kh')->nullable();
                $table->unsignedTinyInteger('target_months')->default(6);
                $table->boolean('is_published')->default(true)->index();
                $table->timestamps();
            }
        );

        $this->createWithFkFallback(
            'path_stages',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('path_id')->constrained('learning_paths')->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(1);
                $table->string('title', 200);
                $table->string('title_kh', 200)->nullable();
                $table->string('description', 500)->nullable();
                $table->string('description_kh', 500)->nullable();
                $table->timestamps();

                $table->index(['path_id', 'position']);
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('path_id');
                $table->unsignedSmallInteger('position')->default(1);
                $table->string('title', 200);
                $table->string('title_kh', 200)->nullable();
                $table->string('description', 500)->nullable();
                $table->string('description_kh', 500)->nullable();
                $table->timestamps();

                $table->index(['path_id', 'position']);
            }
        );

        $this->createWithFkFallback(
            'path_stage_courses',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('stage_id')->constrained('path_stages')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(1);
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->unique(['stage_id', 'course_id']);
                $table->index(['stage_id', 'position']);
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stage_id');
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedSmallInteger('position')->default(1);
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->unique(['stage_id', 'course_id']);
                $table->index(['stage_id', 'position']);
            }
        );

        $this->createWithFkFallback(
            'user_goals',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->foreignId('path_id')->constrained('learning_paths')->cascadeOnDelete();
                $table->unsignedTinyInteger('target_months')->default(6);
                $table->unsignedTinyInteger('weekly_hours')->default(10);
                $table->timestamp('started_at')->nullable();
                $table->timestamps();
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->unsignedBigInteger('path_id')->index();
                $table->unsignedTinyInteger('target_months')->default(6);
                $table->unsignedTinyInteger('weekly_hours')->default(10);
                $table->timestamp('started_at')->nullable();
                $table->timestamps();
            }
        );

        if (!Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('name_kh', 100)->nullable();
                $table->timestamps();
            });
        }

        $this->createWithFkFallback(
            'course_skills',
            function (Blueprint $table) {
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();

                $table->primary(['course_id', 'skill_id']);
            },
            function (Blueprint $table) {
                $table->unsignedBigInteger('course_id');
                $table->unsignedBigInteger('skill_id')->index();

                $table->primary(['course_id', 'skill_id']);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('course_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('user_goals');
        Schema::dropIfExists('path_stage_courses');
        Schema::dropIfExists('path_stages');
        Schema::dropIfExists('learning_paths');
    }

    protected function createWithFkFallback(string $tableName, \Closure $withFks, \Closure $withoutFks): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        try {
            Schema::create($tableName, $withFks);
        } catch (\Throwable $e) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, $withoutFks);
        }
    }
};
