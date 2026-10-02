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
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'semester')) {
                $table->string('semester')->nullable()->after('course_id')->default('Semester 1');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};
