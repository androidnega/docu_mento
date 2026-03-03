<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link deadlines to academic year: Department → Academic Year → Deadline.
     * Different academic years can have different deadlines; projects belong to academic year.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deadlines') || Schema::hasColumn('deadlines', 'academic_year_id')) {
            return;
        }

        Schema::table('deadlines', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('department_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deadlines', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
        });
    }
};
