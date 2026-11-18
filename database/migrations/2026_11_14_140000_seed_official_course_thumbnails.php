<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled dummy course thumbnail seeding for official production.
    }

    public function down(): void
    {
        // No-op
    }
};
