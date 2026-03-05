<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the supervisor_project_approvals table exists for tracking per‑supervisor approvals.
     * Safe on existing databases; does nothing if the table already exists.
     */
    public function up(): void
    {
        if (Schema::hasTable('supervisor_project_approvals')) {
            return;
        }

        Schema::create('supervisor_project_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id'], 'spa_project_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_project_approvals');
    }
};

