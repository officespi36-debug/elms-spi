<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'academic_year')) {
                $table->string('academic_year')->nullable()->default('Academic Year 2024 – 2025')->after('major_id');
            }
            if (!Schema::hasColumn('users', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete()->after('academic_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
            if (Schema::hasColumn('users', 'academic_year')) {
                $table->dropColumn('academic_year');
            }
        });
    }
};
