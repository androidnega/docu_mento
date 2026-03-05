<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the project_files table exists for storing supervisor project-level files.
     * Safe on existing databases; does nothing if the table already exists.
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
        Schema::dropIfExists('project_files');
    }
};

