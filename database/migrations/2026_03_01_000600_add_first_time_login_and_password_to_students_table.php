<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student auth flow: first_time_login → OTP → Account Setup → Dashboard;
     * subsequent: Index → Password → Dashboard.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'first_time_login')) {
                $table->boolean('first_time_login')->default(true);
            }
            if (!Schema::hasColumn('students', 'password')) {
                $table->string('password')->nullable();
            }
            if (!Schema::hasColumn('students', 'is_active')) {
                $table->boolean('is_active')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'first_time_login')) {
                $table->dropColumn('first_time_login');
            }
            if (Schema::hasColumn('students', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('students', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
