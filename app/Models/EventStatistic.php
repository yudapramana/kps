<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStatistic extends Model
{
    protected $fillable = [
        'event_id',
        'total_participants',
        'paid_participants',
        'unpaid_participants',
        'total_registrations',
        'already_registered',
        'not_yet_registered',
        'calculated_at'
    ];
}