<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('study_daily')) {
            Schema::create('study_daily', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('study_date');
                $table->string('kind', 20); // lesson | practice | quiz
                $table->unsignedInteger('seconds')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'study_date', 'kind'], 'study_daily_user_date_kind_unique');
                $table->index(['user_id', 'study_date'], 'study_daily_user_date_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('study_daily');
    }
};
