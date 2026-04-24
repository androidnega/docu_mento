<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_login_stall_indices')) {
            return;
        }

        Schema::create('student_login_stall_indices', function (Blueprint $table) {
            $table->id();
            $table->string('index_normalized', 191)->unique();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        DB::table('student_login_stall_indices')->insert([
            'index_normalized' => 'BC/ICT/22/367',
            'note' => 'Seeded default (Super Admin can remove)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('student_login_stall_indices');
    }
};
