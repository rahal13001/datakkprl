<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class KkprlApplicant extends Authenticatable
{
    protected $fillable = ['email', 'password'];

    protected $hidden = ['password', 'remember_token', 'session_version'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'approved_at' => 'datetime', 'session_version' => 'integer'];
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(KkprlProposal::class, 'applicant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(KkprlApplicantEvent::class, 'applicant_id');
    }
}
