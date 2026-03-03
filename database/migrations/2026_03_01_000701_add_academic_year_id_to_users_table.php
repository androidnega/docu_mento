<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tie users (students) to an academic year for filtering and year-based rules.
     * Chain: User → Academic Year, User → Role, User → Group (via group_members).
     */
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'academic_year_id')) {
            return;
        }
        if (!Schema::hasTable('academic_years')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable()->after('role_id');
        });
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // If FK fails (e.g. different DB), column is already added
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'academic_year_id')) {
            return;
        }
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        } catch (\Throwable $e) {
            // FK may not exist if up() added column only
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('academic_year_id');
        });
    }
};
