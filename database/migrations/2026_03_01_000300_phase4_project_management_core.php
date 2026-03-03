<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: Project Management Core.
     * Project Group → Project → Features, Supervisors.
     * No DB FKs to avoid errno 150; relations in Eloquent.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Deadlines (department-based)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('deadlines')) {
            Schema::create('deadlines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->date('deadline_date');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Project Statuses (lookup)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('project_statuses')) {
            Schema::create('project_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Projects
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('status_id')->nullable()->index();
                $table->unsignedBigInteger('approved_by_id')->nullable()->index();
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                $table->unsignedBigInteger('parent_project_id')->nullable()->index();
                $table->unsignedBigInteger('deadline_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();

                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('approved')->default(false);
                $table->string('status', 20)->default('draft');
                $table->boolean('is_completed')->default(false);
                $table->decimal('budget', 12, 2)->nullable();
                $table->timestamp('approval_date')->nullable();
                $table->string('github_link')->nullable();
                $table->string('project_link')->nullable();
                $table->string('final_submission')->nullable();
                $table->unsignedTinyInteger('max_chapters')->default(6);
                $table->timestamp('submission_deadline')->nullable();

                $table->timestamps();

                $table->index(['group_id', 'status_id']);
            });
        } else {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'status_id')) {
                    $table->unsignedBigInteger('status_id')->nullable()->after('group_id')->index();
                }
                if (!Schema::hasColumn('projects', 'deadline_id')) {
                    $table->unsignedBigInteger('deadline_id')->nullable()->after('category_id')->index();
                }
                if (!Schema::hasColumn('projects', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                }
                if (!Schema::hasColumn('projects', 'parent_project_id')) {
                    $table->unsignedBigInteger('parent_project_id')->nullable()->index();
                }
                if (!Schema::hasColumn('projects', 'approved_by_id')) {
                    $table->unsignedBigInteger('approved_by_id')->nullable()->index();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Project Supervisors (pivot)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('project_supervisors')) {
            Schema::create('project_supervisors', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->primary(['project_id', 'user_id']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Project Features (table: features)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
        Schema::dropIfExists('project_supervisors');
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $cols = ['status_id', 'deadline_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('projects', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('project_statuses');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('categories');
        // Do not drop projects - may exist from other migrations
    }
};
