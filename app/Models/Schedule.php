<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'date',
        'start_time',
        'end_time',
        'is_online',
        'meeting_link',
    ];

    protected $casts = [
        'date' => 'date',
        'is_online' => 'boolean',
        'meeting_link_encrypted' => 'encrypted',
    ];

    protected function meetingLink(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => filled($this->attributes['meeting_link_encrypted'] ?? null)
                ? $this->getAttributeValue('meeting_link_encrypted')
                : $value,
            set: fn ($value) => [
                'meeting_link' => null,
                'meeting_link_encrypted' => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt($value, false),
            ],
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
