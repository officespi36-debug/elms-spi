<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('enrollments', 'seen_at')) {
                    $table->timestamp('seen_at')->nullable();
                }
                if (!Schema::hasColumn('enrollments', 'enrolled_by')) {
                    $table->unsignedBigInteger('enrolled_by')->nullable();
                }
                if (!Schema::hasColumn('enrollments', 'source')) {
                    $table->string('source', 50)->nullable()->default('manual');
                }
            });
        } catch (\Throwable $e) {
            // Columns might already exist
        }

        // Add index on (student_id, seen_at) safely
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['student_id', 'seen_at'], 'enrollments_student_seen_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists or skipped
        }

        // Initialize existing enrollments as seen so they don't all appear as "New"
        try {
            if (Schema::hasColumn('enrollments', 'seen_at')) {
                DB::table('enrollments')
                    ->whereNull('seen_at')
                    ->update(['seen_at' => now()]);
            }
        } catch (\Throwable $e) {
            // Fallback
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('enrollments', 'enrolled_by')) {
                    $table->dropColumn('enrolled_by');
                }
                if (Schema::hasColumn('enrollments', 'source')) {
                    $table->dropColumn('source');
                }
                if (Schema::hasColumn('enrollments', 'seen_at')) {
                    $table->dropColumn('seen_at');
                }
            });
        } catch (\Throwable $e) {}
    }
};

