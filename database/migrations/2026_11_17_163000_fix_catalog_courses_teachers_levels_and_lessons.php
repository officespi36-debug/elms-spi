<?php

use Database\Seeders\CourseMultilingualSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations to ensure production catalog courses have levels, lessons,
     * authentic Khmer metadata, accurate thumbnails, and sanitized instructor names.
     */
    public function up(): void
    {
        (new CourseMultilingualSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: preserve catalog curriculum data
    }
};
