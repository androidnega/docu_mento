<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3: Group & Academic Structuring.
     * Academic Year → Project Group (groups) → Group Members (group_members).
     * No DB FKs to avoid errno 150 with existing tables; relations in Eloquent.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Project Groups (table: groups)
        |--------------------------------------------------------------------------
        |
        | One group per academic year. Name unique per academic_year_id.
        | Optional: enforce one group per user per year via app validation
        | (unique user_id + academic_year_id across group_members + groups).
        |
        */
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                $table->unsignedBigInteger('leader_id')->nullable()->index();
                $table->string('token', 32)->unique();
                $table->timestamps();

                $table->unique(['academic_year_id', 'name']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Group Members (pivot: group_id, user_id)
        |--------------------------------------------------------------------------
        |
        | Composite primary key enforces: no duplicate member in same group.
        |
        */
        if (!Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->primary(['group_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
    }
};
