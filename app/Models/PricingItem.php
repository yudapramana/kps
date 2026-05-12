<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingItem extends Model
{
    protected $fillable = [
        'participant_category_id',
        'package_type',
        'workshop_quota',
        'workshop_count',
        'includes_symposium',
        'price',
        'bird_type'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function participantCategory()
    {
        return $this->belongsTo(ParticipantCategory::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function getPackageLabelAttribute()
    {
        $includesSymposium = (bool) $this->includes_symposium;
        $workshopCount = (int) $this->workshop_count;

        if ($includesSymposium === false && $workshopCount === 1) {
            return 'Workshop for Nurse';
        }

        $label = 'Symposium';

        if ($workshopCount > 0) {
            $workshopText = $workshopCount === 1
                ? '1 Workshop'
                : $workshopCount . ' Workshops';

            $label .= ' + ' . $workshopText;
        }

        return $label;
    }
}
