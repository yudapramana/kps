<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'activity_id',
        'activity_type',
        'paid_participants',
        'calculated_at',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'activity_id' => 'integer',
        'paid_participants' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function getActivityTitleAttribute(): string
    {
        if ($this->activity_type === 'symposium') {
            return 'General Symposium';
        }

        return $this->activity?->title ?? '-';
    }
}