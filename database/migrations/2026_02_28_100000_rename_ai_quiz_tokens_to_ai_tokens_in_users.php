<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
        if (Schema::hasColumn('users', 'ai_quiz_tokens_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_quiz_tokens_allocation', 'ai_tokens_allocation');
            });
        }
        if (Schema::hasColumn('users', 'ai_quiz_tokens_used')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_quiz_tokens_used', 'ai_tokens_used');
            });
        }
        if (Schema::hasColumn('users', 'ai_quiz_tokens_reset_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_quiz_tokens_reset_at', 'ai_tokens_reset_at');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
        if (Schema::hasColumn('users', 'ai_tokens_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_tokens_allocation', 'ai_quiz_tokens_allocation');
            });
        }
        if (Schema::hasColumn('users', 'ai_tokens_used')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_tokens_used', 'ai_quiz_tokens_used');
            });
        }
        if (Schema::hasColumn('users', 'ai_tokens_reset_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('ai_tokens_reset_at', 'ai_quiz_tokens_reset_at');
            });
        }
    }
};
