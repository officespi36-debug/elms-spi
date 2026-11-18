<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_recommendations') && !Schema::hasColumn('ai_recommendations', 'ai_payload')) {
            Schema::table('ai_recommendations', function (Blueprint $table) {
                $table->text('ai_payload')->nullable()->after('reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_recommendations') && Schema::hasColumn('ai_recommendations', 'ai_payload')) {
            Schema::table('ai_recommendations', function (Blueprint $table) {
                $table->dropColumn('ai_payload');
            });
        }
    }
};
