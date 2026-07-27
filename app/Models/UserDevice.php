<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class UserDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'platform',
        'installation_id',
        'device_name',
        'app_version',
        'build_number',
        'push_registration',
        'push_registration_hash',
        'notification_permission',
        'last_registered_at',
        'last_seen_at',
        'disabled_at',
    ];

    protected $hidden = [
        'push_registration',
        'push_registration_encrypted',
        'push_registration_hash',
    ];

    protected $casts = [
        'build_number' => 'integer',
        'last_registered_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'disabled_at' => 'datetime',
        'push_registration_encrypted' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $device): void {
            $device->push_registration_hash = filled($device->push_registration)
                ? hash('sha256', (string) $device->push_registration)
                : null;
        });
    }

    protected function pushRegistration(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->attributes['push_registration_encrypted'] ?? null)
                ? $this->getAttributeValue('push_registration_encrypted')
                : null,
            set: fn ($value) => [
                'push_registration_encrypted' => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt($value, false),
            ],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }
}
