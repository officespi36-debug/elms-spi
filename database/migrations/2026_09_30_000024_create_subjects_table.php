<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('name_kh')->nullable();
                $table->integer('credits')->default(3);
                $table->string('prerequisite')->nullable()->default('None');
                $table->string('difficulty')->default('Beginner');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'subject_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('major_id')->constrained('subjects')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'subject_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            });
        }

        Schema::dropIfExists('subjects');
    }
};
