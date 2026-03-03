<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Academic years (e.g. "2023/2024"). Used by Docu Mentor groups, projects, academic classes.
     * Phase 1 migration adds department_id and unique(department_id, year) when this table exists.
     */
    public function up(): void
    {
        if (Schema::hasTable('academic_years')) {
            return;
        }

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->boolean('is_active')->default(false);
            $table->date('submission_deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
