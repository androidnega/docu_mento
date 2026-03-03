<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('index_number')->index();
            $table->string('index_number_hash', 64)->unique();
            $table->string('phone_contact', 50)->nullable()->index();
            $table->string('student_name')->nullable();
            $table->integer('level')->nullable();
            $table->boolean('first_time_login')->default(true);
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

