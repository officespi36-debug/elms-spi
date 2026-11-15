<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\LessonContentSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run LessonContentSeeder to populate authentic curriculum & lessons
        (new LessonContentSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: preserve user learning state
    }
};
