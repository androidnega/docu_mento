<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove examiner role: users.role 'examiner' → 'supervisor'.
     * Rename class_groups.examiner_id → supervisor_id (same semantics: group owner/supervisor).
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'examiner')->update(['role' => 'supervisor']);
        }
        if (Schema::hasTable('otps')) {
            DB::table('otps')->where('type', 'examiner_fallback')->update(['type' => 'supervisor_fallback']);
        }

        if (Schema::hasTable('class_groups') && Schema::hasColumn('class_groups', 'examiner_id')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->renameColumn('examiner_id', 'supervisor_id');
            });
        }

        if (Schema::hasTable('class_group_course') && Schema::hasColumn('class_group_course', 'examiner_id')) {
            Schema::table('class_group_course', function (Blueprint $table) {
                $table->renameColumn('examiner_id', 'supervisor_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'supervisor')->update(['role' => 'examiner']);
        }

        if (Schema::hasTable('class_groups') && Schema::hasColumn('class_groups', 'supervisor_id')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->renameColumn('supervisor_id', 'examiner_id');
            });
        }

        if (Schema::hasTable('class_group_course') && Schema::hasColumn('class_group_course', 'supervisor_id')) {
            Schema::table('class_group_course', function (Blueprint $table) {
                $table->renameColumn('supervisor_id', 'examiner_id');
            });
        }

        if (Schema::hasTable('otps')) {
            DB::table('otps')->where('type', 'supervisor_fallback')->update(['type' => 'examiner_fallback']);
        }
    }
};
