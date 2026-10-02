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
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'semester')) {
                $table->string('semester', 100)->nullable()->after('academic_year');
            }
            if (!Schema::hasColumn('courses', 'semester_id')) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('semester');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'semester_id')) {
                $table->dropColumn('semester_id');
            }
            if (Schema::hasColumn('courses', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};
