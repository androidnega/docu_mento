<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_ai_reviews')) {
            return;
        }

        Schema::create('document_ai_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('ai_output');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index(['submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_reviews');
    }
};
