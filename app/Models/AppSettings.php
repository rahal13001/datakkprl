<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppSettings extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_settings'; // Explicit table name just in case

    protected $fillable = [
        'key',
        'value',
    ];
}
