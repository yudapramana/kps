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

    protected $casts = [
        'event_id' => 'integer',
        'total_participants' => 'integer',
        'paid_participants' => 'integer',
        'unpaid_participants' => 'integer',
        'total_registrations' => 'integer',
        'already_registered' => 'integer',
        'not_yet_registered' => 'integer',
        'calculated_at' => 'datetime',
    ];
}