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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'github_id')) {
                $table->string('github_id')->nullable()->unique()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'github_username')) {
                $table->string('github_username')->nullable()->after('github_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'github_username')) {
                $table->dropColumn('github_username');
            }
            if (Schema::hasColumn('users', 'github_id')) {
                $table->dropColumn('github_id');
            }
        });
    }
};
