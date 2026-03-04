<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the otps table exists for student login and supervisor fallback OTPs.
     * Safe to run on existing databases; does nothing if the table already exists.
     */
    public function up(): void
    {
        if (Schema::hasTable('otps')) {
            return;
        }

        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('index_number_hash', 191);
            $table->string('type', 50);
            $table->string('code', 16);
            $table->string('phone', 32)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['index_number_hash', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};

