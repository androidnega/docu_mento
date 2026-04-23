<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SmsLog::logSend uses phone + string status; older migration created status_id as NOT NULL without phone.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::table('sms_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_logs', 'phone')) {
                $table->string('phone', 64)->nullable()->after('id');
            }
            if (! Schema::hasColumn('sms_logs', 'status')) {
                $table->string('status', 32)->nullable()->after('message');
            }
        });

        if (Schema::hasColumn('sms_logs', 'status_id')) {
            Schema::table('sms_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('status_id')->nullable()->change();
            });
        }
        if (Schema::hasColumn('sms_logs', 'contact_id')) {
            Schema::table('sms_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('sms_statuses') && DB::table('sms_statuses')->count() === 0) {
            foreach (['success', 'failed', 'pending', 'delivered'] as $name) {
                DB::table('sms_statuses')->insertOrIgnore(['name' => $name]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_logs')) {
            return;
        }
        Schema::table('sms_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sms_logs', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('sms_logs', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
