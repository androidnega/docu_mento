<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Academic classes (e.g. "BTECH IT Level 100"). Used by class groups and coordinator students.
     * References academic_years; level_id optional if student_levels not used.
     */
    public function up(): void
    {
        if (Schema::hasTable('academic_classes')) {
            return;
        }

        Schema::create('academic_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('level_id')->nullable()->index();
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_classes');
    }
};
