<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'subject',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

class AiChatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'question',
        'response',
        'ip_address',
    ];
}
