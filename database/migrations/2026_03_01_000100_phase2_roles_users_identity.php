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
        | Roles (per Department)
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                // Use a plain unsignedBigInteger here to avoid FK type/engine mismatches
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->string('name');
                $table->timestamps();

                $table->unique(['department_id', 'name']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Users: attach role_id and is_active
        |--------------------------------------------------------------------------
        |
        | We do not alter existing role string logic; instead we add a nullable
        | role_id that references the roles table with restrictOnDelete, and an
        | is_active flag for soft activation.
        |
        */

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role_id')) {
                    $table->unsignedBigInteger('role_id')->nullable()->after('role')->index();
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')
                        ->default(true)
                        ->after('department_id');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Contacts: multiple per user
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('phone', 32);
                $table->timestamps();
                $table->unique(['user_id', 'phone']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisors: one-to-one extension of User
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('supervisors')) {
            Schema::create('supervisors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supervisors')) {
            Schema::dropIfExists('supervisors');
        }

        if (Schema::hasTable('contacts')) {
            Schema::dropIfExists('contacts');
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role_id')) {
                    $table->dropColumn('role_id');
                }
                if (Schema::hasColumn('users', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }
};

