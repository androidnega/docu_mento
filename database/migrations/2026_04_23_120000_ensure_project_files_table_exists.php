<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recreate project_files if missing (e.g. migration row recorded but table dropped,
     * or an older deploy never ran 2026_03_05_000002). Idempotent.
     */
    public function up(): void
    {
        if (Schema::hasTable('project_files')) {
            return;
        }

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('brief_pdf')->nullable();
            $table->string('diary_pdf')->nullable();
            $table->string('assessment_file')->nullable();
            $table->string('assessment_form_file')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Do not drop: table may predate this migration; only this migration's creation is reversible in isolation.
    }
};
