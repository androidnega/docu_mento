<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = ['user_id', 'phone'];

    protected static function booted(): void
    {
        static::deleting(function (Contact $contact) {
            $contact->smsLogs()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** SMS logs for this contact. Cascade delete in app when contact is deleted. */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'contact_id');
    }
}

