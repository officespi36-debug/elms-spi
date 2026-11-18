<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled sample course thumbnail & student enrollment seeding for official production.
    }

    public function down(): void
    {
        // No-op
    }
};
