<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
