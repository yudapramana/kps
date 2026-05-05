<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'package_code',
        'package_label',
        'includes_symposium',
        'workshop_count',
        'paid_participants',
        'calculated_at',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'includes_symposium' => 'boolean',
        'workshop_count' => 'integer',
        'paid_participants' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}