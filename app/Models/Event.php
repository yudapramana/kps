<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date'            => 'date:Y-m-d',
        'end_date'              => 'date:Y-m-d',
        'early_bird_end_date'   => 'date:Y-m-d',
        'is_active'             => 'boolean',
        'submission_open_at'     => 'datetime:Y-m-d H:i',
        'submission_deadline_at' => 'datetime:Y-m-d H:i',
        'notification_date'      => 'datetime:Y-m-d H:i',
        'submission_close_at'    => 'datetime:Y-m-d H:i',
    ];

    public function days()
    {
        return $this->hasMany(EventDay::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
}
