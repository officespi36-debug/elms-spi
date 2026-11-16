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
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'seen_at')) {
                $table->timestamp('seen_at')->nullable()->after('enrolled_at');
            }
            if (!Schema::hasColumn('enrollments', 'enrolled_by')) {
                $table->foreignId('enrolled_by')->nullable()->after('seen_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('enrollments', 'source')) {
                $table->string('source', 50)->nullable()->after('enrolled_by')->default('manual');
            }
        });

        // Add index on (student_id, seen_at) safely
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['student_id', 'seen_at'], 'enrollments_student_seen_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists or skipped
        }

        // Initialize existing enrollments as seen so they don't all appear as "New"
        DB::table('enrollments')
            ->whereNull('seen_at')
            ->update(['seen_at' => DB::raw('COALESCE(created_at, NOW())')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'enrolled_by')) {
                $table->dropForeign(['enrolled_by']);
                $table->dropColumn('enrolled_by');
            }
            if (Schema::hasColumn('enrollments', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('enrollments', 'seen_at')) {
                try {
                    $table->dropIndex('enrollments_student_seen_idx');
                } catch (\Throwable $e) {}
                $table->dropColumn('seen_at');
            }
        });
    }
};
