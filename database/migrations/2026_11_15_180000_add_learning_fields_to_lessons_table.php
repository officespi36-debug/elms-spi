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
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'summary')) {
                $table->text('summary')->nullable()->after('title');
            }
            if (!Schema::hasColumn('lessons', 'objectives')) {
                $table->json('objectives')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('lessons', 'preview_url')) {
                $table->string('preview_url')->nullable()->after('video_url');
            }
            if (!Schema::hasColumn('lessons', 'video_provider')) {
                $table->string('video_provider', 50)->nullable()->after('preview_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $cols = ['summary', 'objectives', 'preview_url', 'video_provider'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('lessons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
