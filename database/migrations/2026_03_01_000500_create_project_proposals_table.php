<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Docu Mentor: versioned project proposals. Model uses $timestamps = false.
     */
    public function up(): void
    {
        if (Schema::hasTable('project_proposals')) {
            return;
        }

        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('file');
            $table->unsignedBigInteger('uploaded_by_id')->nullable()->index();
            $table->integer('version_number')->default(1);
            $table->text('comment')->nullable();
            $table->text('coordinator_comment')->nullable();
            $table->timestamp('uploaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
    }
};
