<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'nik',
        'full_name',
        'email',
        'mobile_phone',
        'institution',
        'participant_category_id',
        'registration_type',
    ];

    protected $appends = [
        'is_paid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participantCategory()
    {
        return $this->belongsTo(ParticipantCategory::class, 'participant_category_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function registration()
    {
        return $this->hasOne(Registration::class);
    }

    public function papers()
    {
        return $this->hasMany(Paper::class);
    }

    public function getIsPaidAttribute()
    {
        if ($this->relationLoaded('registration')) {
            return optional($this->registration)->payment_step === 'paid';
        }

        return $this->registration()
            ->where('payment_step', 'paid')
            ->exists();
    }

    public function registrationItems()
    {
        return $this->hasManyThrough(
            \App\Models\RegistrationItem::class,
            \App\Models\Registration::class,
            'participant_id',   // Foreign key on registrations table
            'registration_id',  // Foreign key on registration_items table
            'id',               // Local key on participants table
            'id'                // Local key on registrations table
        );
    }
}
