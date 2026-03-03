<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Document & Submission Engine.
     * Project → Proposals (versioned) → Chapters → Submissions → Comments → AI Generations.
     * File abstraction layer. No DB FKs to avoid errno 150.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Files (abstraction layer)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Proposals (versioned; project_id, file_id, submitted_by, version_number)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('proposals')) {
            Schema::create('proposals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('file_id')->index();
                $table->unsignedBigInteger('submitted_by')->index();
                $table->unsignedInteger('version_number')->default(1);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['project_id', 'version_number']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Chapters
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('chapters')) {
            Schema::create('chapters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('title');
                $table->unsignedSmallInteger('order')->default(1);
                $table->boolean('is_open')->default(true);
                $table->boolean('completed')->default(false);
                $table->unsignedSmallInteger('max_score')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'order']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Submissions (chapter_id, file_id, submitted_by; one per chapter per user)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chapter_id')->index();
                $table->unsignedBigInteger('file_id')->nullable()->index();
                $table->unsignedBigInteger('uploaded_by_id')->index();
                $table->string('file')->nullable();
                $table->text('comment')->nullable();
                $table->unsignedSmallInteger('score')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->boolean('is_open')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['chapter_id', 'uploaded_by_id']);
            });
        } else {
            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'file_id')) {
                    $table->unsignedBigInteger('file_id')->nullable()->after('chapter_id')->index();
                }
                if (!Schema::hasColumn('submissions', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Comments (submission_id, user_id, comment_text)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('submission_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('comment_text');
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | AI Generations (submission_id, ai_output_json)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('ai_generations')) {
            Schema::create('ai_generations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('submission_id')->index();
                $table->json('ai_output_json');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('comments');
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (Schema::hasColumn('submissions', 'file_id')) {
                    $table->dropColumn('file_id');
                }
                if (Schema::hasColumn('submissions', 'deleted_at')) {
                    $table->dropColumn('deleted_at');
                }
            });
        }
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('files');
    }
};
