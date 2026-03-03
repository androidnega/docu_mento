<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsStatus extends Model
{
    public $timestamps = false;

    protected $table = 'sms_statuses';

    protected $fillable = ['name'];

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'status_id');
    }

    /** Whether this status indicates success (for retry logic). */
    public function isSuccess(): bool
    {
        return in_array(strtolower($this->name ?? ''), ['success', 'delivered', 'sent'], true);
    }

    /** Whether retries are recommended (e.g. failed, pending). */
    public function shouldRetry(): bool
    {
        return in_array(strtolower($this->name ?? ''), ['failed', 'pending', 'queued'], true);
    }

    /** restrictOnDelete: prevent deletion if any SMS log uses this status. */
    protected static function booted(): void
    {
        static::deleting(function (SmsStatus $status) {
            if ($status->smsLogs()->exists()) {
                throw new \RuntimeException('Cannot delete SMS status: it is in use by sms_logs.');
            }
        });
    }
}
