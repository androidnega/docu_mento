<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed default semesters (1, 2) if table is empty.
     */
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('semesters')) {
            return;
        }
        if (DB::table('semesters')->exists()) {
            return;
        }
        $now = now();
        DB::table('semesters')->insert([
            ['value' => '1', 'name' => 'Semester 1', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['value' => '2', 'name' => 'Semester 2', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('semesters')) {
            DB::table('semesters')->whereIn('value', ['1', '2'])->delete();
        }
    }
};
