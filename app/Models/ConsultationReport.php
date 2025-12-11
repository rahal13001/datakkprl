<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'content',
        'status',
        'reviewed_by',
        'reviewed_at',
        'feedback',
        'documentation',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'documentation' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
