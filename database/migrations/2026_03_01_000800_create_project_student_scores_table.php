<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-student per-supervisor scores for a project.
     * Model: App\Models\DocuMentor\ProjectStudentScore (table: project_student_scores).
     */
    public function up(): void
    {
        if (Schema::hasTable('project_student_scores')) {
            return;
        }

        Schema::create('project_student_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('supervisor_id')->index();
            $table->unsignedSmallInteger('document_score')->nullable();
            $table->unsignedSmallInteger('system_score')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'student_id', 'supervisor_id'], 'project_student_supervisor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_student_scores');
    }
};

