<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'participant_category_id' => $this->participant_category_id,
            'participant_category' => [
                'id' => $this->participantCategory->id,
                'name' => $this->participantCategory->name,
            ],

            'bird_type' => $this->bird_type,

            'includes_symposium' => (bool) $this->includes_symposium,
            'workshop_count' => (int) $this->workshop_count,

            // 🔹 GLOBAL DERIVED FIELD
            'package_label' => $this->packageLabel(),

            'price' => (float) $this->price,
        ];
    }

    protected function packageLabel(): string
    {
        $label = '-';
        if (!$this->includes_symposium && $this->workshop_count == 1) {
            $label = 'Workshop for Nurse';
        }
        elseif ($this->workshop_count == 0) {
            $label = 'Symposium';
        }
        elseif ($this->workshop_count == 1) {
            $label = 'Symposium + 1 Workshop';
        }
        elseif ($this->workshop_count == 2) {
            $label = 'Symposium + 2 Workshop';
        }

        return $label;
    }
}
