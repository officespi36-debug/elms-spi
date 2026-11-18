<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTableWithFkFallback(
            'ai_conversations',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->string('title', 120);
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'last_message_at']);
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id')->nullable();
                $table->string('title', 120);
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'last_message_at']);
                $table->index('course_id');
            }
        );

        $this->createTableWithFkFallback(
            'ai_messages',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                $table->string('role', 20);
                $table->text('body');
                $table->string('provider', 40)->nullable();
                $table->string('model', 100)->nullable();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedInteger('tokens')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'id']);
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->string('role', 20);
                $table->text('body');
                $table->string('provider', 40)->nullable();
                $table->string('model', 100)->nullable();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedInteger('tokens')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'id']);
            }
        );

        $this->createTableWithFkFallback(
            'ai_feedback',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('rating', 10);
                $table->string('note', 500)->nullable();
                $table->timestamps();

                $table->unique(['message_id', 'user_id']);
            },
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('user_id');
                $table->string('rating', 10);
                $table->string('note', 500)->nullable();
                $table->timestamps();

                $table->unique(['message_id', 'user_id']);
                $table->index('user_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }

    private function createTableWithFkFallback(string $tableName, \Closure $withFks, \Closure $withoutFks): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        try {
            Schema::create($tableName, $withFks);
        } catch (\Throwable $e) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, $withoutFks);
        }
    }
};
