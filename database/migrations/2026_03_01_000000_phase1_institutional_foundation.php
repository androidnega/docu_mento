<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Schools
        |--------------------------------------------------------------------------
        |
        | Top-level academic units. Everything else (departments, academic years)
        | hangs off this backbone.
        |
        */

        if (!Schema::hasTable('schools')) {
            Schema::create('schools', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        |
        | If a legacy departments table already exists, we just attach it to
        | schools and add an is_active flag. If it does not exist at all, we
        | create a minimal departments table suitable for the new backbone.
        |
        */

        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                // Legacy compatibility: keep faculty_id nullable even though
                // we're moving away from institutions/faculties.
                $table->unsignedBigInteger('faculty_id')->nullable()->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['school_id', 'name'], 'departments_school_name_unique');
            });
        } else {
            Schema::table('departments', function (Blueprint $table) {
                if (! Schema::hasColumn('departments', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->nullable()->after('faculty_id')->index();
                }
                if (! Schema::hasColumn('departments', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('school_id');
                }
            });

            // Unique department per school (name scoped to school)
            if (Schema::hasColumn('departments', 'school_id')) {
                Schema::table('departments', function (Blueprint $table) {
                    $table->unique(['school_id', 'name'], 'departments_school_name_unique');
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Academic Years: attach to departments
        |--------------------------------------------------------------------------
        |
        | We reuse the existing academic_years table used by Docu Mentor, adding
        | department connectivity and uniqueness per department.
        |
        */

        if (Schema::hasTable('academic_years')) {
            Schema::table('academic_years', function (Blueprint $table) {
                if (!Schema::hasColumn('academic_years', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('id')->index();
                }
            });

            // Unique academic year per department (by year string)
            if (Schema::hasColumn('academic_years', 'department_id')) {
                Schema::table('academic_years', function (Blueprint $table) {
                    $table->unique(['department_id', 'year'], 'academic_years_department_year_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_years')) {
            Schema::table('academic_years', function (Blueprint $table) {
                if (Schema::hasColumn('academic_years', 'department_id')) {
                    $table->dropUnique('academic_years_department_year_unique');
                    $table->dropColumn('department_id');
                }
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (Schema::hasColumn('departments', 'school_id')) {
                    $table->dropUnique('departments_school_name_unique');
                    $table->dropColumn('school_id');
                }
                if (Schema::hasColumn('departments', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        Schema::dropIfExists('schools');
    }
};

