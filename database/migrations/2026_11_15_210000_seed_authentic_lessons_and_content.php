<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled LessonContentSeeder auto-run for official production.
    }

    public function down(): void
    {
        // No-op
    }
};
