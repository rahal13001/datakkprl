<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'city_id',
        'activity_code',
        'detail',
        'date',
        'organizer',
        'application_size',
        'technical_assessment_size',
        'unit',
        'pnbp_potential',
        'zero_rupiah_incentive',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'application_size' => 'decimal:2',
        'technical_assessment_size' => 'decimal:2',
        'pnbp_potential' => 'decimal:2',
        'zero_rupiah_incentive' => 'decimal:2',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
