<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KkprlApplicantEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['applicant_id', 'actor_id', 'action', 'root_proposal_id', 'reason', 'verification_reference', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'reason' => 'encrypted', 'verification_reference' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Applicant events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Applicant events are append-only.'));
    }
}
