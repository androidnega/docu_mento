<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Semesters (e.g. 1, 2). Used by class groups and coordinator students.
     */
    public function up(): void
    {
        if (Schema::hasTable('semesters')) {
            return;
        }

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('value', 64)->nullable();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
