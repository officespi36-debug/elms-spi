<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create course_approval_histories table if not exists
        if (!Schema::hasTable('course_approval_histories')) {
            Schema::create('course_approval_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // 'submitted', 'approved', 'rejected', 'resubmitted'
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        // 2. Modify enrollments table status column to accept 'active', 'completed', 'dropped'
        if (Schema::hasTable('enrollments')) {
            // Using DB raw statement to alter enum safely or string column
            try {
                DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('active','completed','dropped','cancelled','pending_payment','expired') NOT NULL DEFAULT 'active'");
            } catch (\Throwable $e) {
                // In case DB doesn't support enum alteration or already string
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_approval_histories');
    }
};
