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
        // 1. Add discussion fields to lesson_comments
        Schema::table('lesson_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('lesson_comments', 'video_seconds')) {
                $table->unsignedInteger('video_seconds')->nullable()->after('body');
            }
            if (!Schema::hasColumn('lesson_comments', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('video_seconds');
            }
            if (!Schema::hasColumn('lesson_comments', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_pinned');
            }
            if (!Schema::hasColumn('lesson_comments', 'is_answered')) {
                $table->boolean('is_answered')->default(false)->after('edited_at');
            }
            if (!Schema::hasColumn('lesson_comments', 'answered_comment_id')) {
                $table->foreignId('answered_comment_id')
                    ->nullable()
                    ->after('is_answered')
                    ->constrained('lesson_comments')
                    ->onDelete('set null');
            }
        });

        foreach ([
            ['lesson_id', 'parent_id', 'created_at'],
            ['lesson_id', 'is_answered'],
        ] as $columns) {
            try {
                Schema::table('lesson_comments', fn (Blueprint $table) => $table->index($columns));
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }

        // 2. Create lesson_comment_likes table
        if (!Schema::hasTable('lesson_comment_likes')) {
            Schema::create('lesson_comment_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comment_id')->constrained('lesson_comments')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['comment_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_comment_likes');

        Schema::table('lesson_comments', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_comments', 'answered_comment_id')) {
                $table->dropForeign(['answered_comment_id']);
                $table->dropColumn('answered_comment_id');
            }
            if (Schema::hasColumn('lesson_comments', 'is_answered')) {
                $table->dropColumn('is_answered');
            }
            if (Schema::hasColumn('lesson_comments', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
            if (Schema::hasColumn('lesson_comments', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
            if (Schema::hasColumn('lesson_comments', 'video_seconds')) {
                $table->dropColumn('video_seconds');
            }
        });
    }
};
