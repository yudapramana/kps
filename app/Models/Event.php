<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'theme',
        'description',
        'start_date',
        'end_date',
        'location',
        'venue',
        'is_active',
        'early_bird_end_date',
    ];

    protected $casts = [
        'start_date'            => 'date:Y-m-d',
        'end_date'              => 'date:Y-m-d',
        'early_bird_end_date'   => 'date:Y-m-d',
        'is_active'             => 'boolean',
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
