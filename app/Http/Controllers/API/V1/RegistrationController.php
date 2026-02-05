<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    
    public function index(Request $request)
    {
        $eventId = $request->get('event_id');
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Registration::query()
            ->with([
                // PARTICIPANT
                'participant:id,full_name,email,participant_category_id',
                'participant.participantCategory:id,name',

                // PRICING
                'pricingItem:id,participant_category_id,bird_type,workshop_count,price',
                'pricingItem.participantCategory:id,name',

                // BANK
                'bank:id,name,account_number,account_name',

                // EVENT
                'event:id,name',

                // OPTIONAL (future)
                'items.activity:id,title,category',
            ])
            ->orderByDesc('created_at');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($search) {
            $query->whereHas('participant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }


}
