<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns expected by App\Models\DocuMentor\Project (approved, status, is_completed, etc.)
     * when missing (e.g. projects table was created without them).
     */
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'approved')) {
                $table->boolean('approved')->default(false)->after('description');
            }
            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status', 20)->default('draft')->after('approved');
            }
            if (!Schema::hasColumn('projects', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('status');
            }
            if (!Schema::hasColumn('projects', 'github_link')) {
                $table->string('github_link')->nullable()->after('approval_date');
            }
            if (!Schema::hasColumn('projects', 'project_link')) {
                $table->string('project_link')->nullable()->after('github_link');
            }
            if (!Schema::hasColumn('projects', 'final_submission')) {
                $table->string('final_submission')->nullable()->after('project_link');
            }
            if (!Schema::hasColumn('projects', 'max_chapters')) {
                $table->unsignedTinyInteger('max_chapters')->default(6)->after('final_submission');
            }
            if (!Schema::hasColumn('projects', 'submission_deadline')) {
                $table->timestamp('submission_deadline')->nullable()->after('max_chapters');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $cols = ['approved', 'status', 'is_completed', 'github_link', 'project_link', 'final_submission', 'max_chapters', 'submission_deadline'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
