<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Notification & communication tracking. Contact → SMS Log → Status.
 * Store response, track delivery status. restrictOnDelete for status (app-level);
 * cascade on contact delete (delete logs when contact is deleted in app logic).
 */
class SmsLog extends Model
{
    protected $table = 'sms_logs';

    public $timestamps = true;

    protected $fillable = ['phone', 'message', 'status', 'response', 'user_id', 'status_id', 'contact_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function statusRef(): BelongsTo
    {
        return $this->belongsTo(SmsStatus::class, 'status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Log an SMS send (legacy: phone + string status; optional status_id when sms_statuses exists). */
    public static function logSend(string $phone, string $message, bool $success, ?string $responseMessage = null, ?int $userId = null): self
    {
        $data = [
            'phone' => $phone,
            'message' => $message,
            'status' => $success ? 'success' : 'failed',
            'response' => $responseMessage,
            'user_id' => $userId,
        ];
        if (Schema::hasColumn('sms_logs', 'status_id')) {
            $name = $success ? 'success' : 'failed';
            $id = SmsStatus::query()->where('name', $name)->value('id');
            if ($id !== null) {
                $data['status_id'] = $id;
            }
        }

        return self::create($data);
    }

    /** Log with status_id and optional contact_id (track delivery, retry by status). */
    public static function logWithStatus(
        int $statusId,
        string $message,
        ?string $response = null,
        ?int $contactId = null,
        ?int $userId = null
    ): self {
        return self::create([
            'status_id' => $statusId,
            'contact_id' => $contactId,
            'message' => $message,
            'response' => $response,
            'user_id' => $userId,
        ]);
    }
}
