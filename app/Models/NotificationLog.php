<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'channel',
        'destination',
        'message_body',
        'status',
    ];

    protected $casts = [
        'destination_encrypted' => 'encrypted',
        'message_body_encrypted' => 'encrypted',
    ];

    protected function destination(): Attribute
    {
        return $this->protectedAttribute('destination', 'destination_encrypted');
    }

    protected function messageBody(): Attribute
    {
        return $this->protectedAttribute('message_body', 'message_body_encrypted');
    }

    protected function protectedAttribute(string $legacyColumn, string $encryptedColumn)
    {
        return Attribute::make(
            get: fn ($value) => filled($this->attributes[$encryptedColumn] ?? null)
                ? $this->getAttributeValue($encryptedColumn)
                : $value,
            set: fn ($value) => [
                $legacyColumn => null,
                $encryptedColumn => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt($value, false),
            ],
        );
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
