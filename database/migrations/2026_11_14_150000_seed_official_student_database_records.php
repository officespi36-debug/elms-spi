<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled sample student/enrollment/certificate/progress seeding for official production.
    }

    public function down(): void
    {
        // No-op
    }
};
