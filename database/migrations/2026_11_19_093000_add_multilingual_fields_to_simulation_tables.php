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
        if (Schema::hasTable('simulations') && !Schema::hasColumn('simulations', 'instructions_kh')) {
            Schema::table('simulations', function (Blueprint $table) {
                $table->text('instructions_kh')->nullable()->after('instructions');
            });
        }

        if (Schema::hasTable('simulation_nodes')) {
            Schema::table('simulation_nodes', function (Blueprint $table) {
                if (!Schema::hasColumn('simulation_nodes', 'title_kh')) {
                    $table->string('title_kh')->nullable()->after('title');
                }
                if (!Schema::hasColumn('simulation_nodes', 'body_kh')) {
                    $table->longText('body_kh')->nullable()->after('body');
                }
                if (!Schema::hasColumn('simulation_nodes', 'feedback_kh')) {
                    $table->text('feedback_kh')->nullable()->after('feedback');
                }
            });
        }

        if (Schema::hasTable('simulation_choices')) {
            Schema::table('simulation_choices', function (Blueprint $table) {
                if (!Schema::hasColumn('simulation_choices', 'label_kh')) {
                    $table->text('label_kh')->nullable()->after('label');
                }
                if (!Schema::hasColumn('simulation_choices', 'feedback_kh')) {
                    $table->text('feedback_kh')->nullable()->after('feedback');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('simulations') && Schema::hasColumn('simulations', 'instructions_kh')) {
            Schema::table('simulations', function (Blueprint $table) {
                $table->dropColumn('instructions_kh');
            });
        }

        if (Schema::hasTable('simulation_nodes')) {
            Schema::table('simulation_nodes', function (Blueprint $table) {
                $table->dropColumn(['title_kh', 'body_kh', 'feedback_kh']);
            });
        }

        if (Schema::hasTable('simulation_choices')) {
            Schema::table('simulation_choices', function (Blueprint $table) {
                $table->dropColumn(['label_kh', 'feedback_kh']);
            });
        }
    }
};
