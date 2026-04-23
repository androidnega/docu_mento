<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }
        if (! Schema::hasColumn('students', 'legal_name_completed_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->timestamp('legal_name_completed_at')->nullable()->after('student_name');
            });
        }

        Student::query()->orderBy('id')->chunk(200, function ($students) {
            foreach ($students as $student) {
                if ($student->legal_name_completed_at !== null) {
                    continue;
                }
                $idx = trim((string) ($student->index_number ?? ''));
                $sn = trim((string) ($student->student_name ?? ''));
                if ($sn !== '' && ! User::docuMentorNameIsIndexNumber($sn, $idx, null)) {
                    $student->legal_name_completed_at = $student->updated_at ?? now();
                    $student->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'legal_name_completed_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('legal_name_completed_at');
            });
        }
    }
};
