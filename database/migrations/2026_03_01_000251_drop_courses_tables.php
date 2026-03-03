<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove course-related tables and user.course_id.
     * System uses project/academic structure only; no courses module.
     */
    public function up(): void
    {
        Schema::dropIfExists('class_group_course');
        Schema::dropIfExists('course_user');
        Schema::dropIfExists('courses');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'course_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('course_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
        if (!Schema::hasColumn('users', 'course_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable()->after('name')->index();
            });
        }

        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 64)->unique();
                $table->boolean('is_archived')->default(false);
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('level_id')->nullable()->index();
                $table->unsignedBigInteger('semester_id')->nullable()->index();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('course_user')) {
            Schema::create('course_user', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->primary(['course_id', 'user_id']);
            });
        }
        if (!Schema::hasTable('class_group_course')) {
            Schema::create('class_group_course', function (Blueprint $table) {
                $table->unsignedBigInteger('class_group_id')->index();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('examiner_id')->nullable()->index();
                $table->timestamps();
                $table->primary(['class_group_id', 'course_id']);
            });
        }
    }
};
