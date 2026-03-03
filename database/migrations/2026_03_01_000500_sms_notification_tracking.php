<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notification & communication tracking.
     * Contact → SMS Log → Status.
     * restrictOnDelete for status (app-level); cascade on contact delete (app-level).
     * No DB FKs to avoid errno 150.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sms_statuses')) {
            Schema::create('sms_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
            });
        }

        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('status_id')->index();
                $table->unsignedBigInteger('contact_id')->index();
                $table->text('message');
                $table->text('response')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('sms_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_logs', 'status_id')) {
                    $table->unsignedBigInteger('status_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('sms_logs', 'contact_id')) {
                    $table->unsignedBigInteger('contact_id')->nullable()->after('status_id')->index();
                }
                if (!Schema::hasColumn('sms_logs', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sms_logs')) {
            Schema::table('sms_logs', function (Blueprint $table) {
                if (Schema::hasColumn('sms_logs', 'status_id')) {
                    $table->dropColumn('status_id');
                }
                if (Schema::hasColumn('sms_logs', 'contact_id')) {
                    $table->dropColumn('contact_id');
                }
            });
        }
        Schema::dropIfExists('sms_statuses');
    }
};
