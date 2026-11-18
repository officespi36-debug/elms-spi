<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'title_kh')) {
                $table->string('title_kh')->nullable()->after('title');
            }
            if (!Schema::hasColumn('courses', 'description_kh')) {
                $table->text('description_kh')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'title_kh')) {
                $table->dropColumn('title_kh');
            }
            if (Schema::hasColumn('courses', 'description_kh')) {
                $table->dropColumn('description_kh');
            }
        });
    }
};
