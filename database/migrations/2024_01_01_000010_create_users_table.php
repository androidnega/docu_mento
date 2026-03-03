<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the base users table (staff: super_admin, supervisor; Docu Mentor: student, leader, coordinator, etc.).
     * Phase 2 adds role_id and is_active if not present; this migration creates the table so it exists before Phase 2.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 32)->nullable();
            $table->string('index_number', 64)->nullable()->index();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('role', 64)->nullable()->index();
            $table->string('password')->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->unsignedInteger('sms_allocation')->nullable()->default(0);
            $table->unsignedInteger('sms_used')->nullable()->default(0);
            $table->unsignedInteger('ai_tokens_allocation')->nullable()->default(0);
            $table->unsignedInteger('ai_tokens_used')->nullable()->default(0);
            $table->timestamp('ai_tokens_reset_at')->nullable();
            $table->unsignedBigInteger('faculty_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->boolean('group_leader')->default(false);
            $table->boolean('coordinator')->default(false);
            $table->unsignedBigInteger('role_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
