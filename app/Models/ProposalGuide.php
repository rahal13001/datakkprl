<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalGuide extends Model
{
    protected $fillable = ['title', 'description', 'pdf_path', 'sort_order', 'is_published'];

    protected $casts = ['sort_order' => 'integer', 'is_published' => 'boolean'];
}
