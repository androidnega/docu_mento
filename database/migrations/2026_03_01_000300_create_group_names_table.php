<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Docu Mentor: suggested group names (genz_word + tech_word) for create-group page.
     * Optional department_id for department-specific suggestions; null = global.
     */
    public function up(): void
    {
        if (Schema::hasTable('group_names')) {
            return;
        }

        Schema::create('group_names', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('genz_word');
            $table->string('tech_word');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_names');
    }
};
