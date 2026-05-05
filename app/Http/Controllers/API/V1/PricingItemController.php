<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PricingItemResource;
use App\Models\PricingItem;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PricingItemController extends Controller
{
    public function index(Request $request)
    {
        $query = PricingItem::with('participantCategory')
            ->orderBy('participant_category_id')
            ->orderBy('bird_type')
            ->orderBy('workshop_count');

        if ($request->filled('participant_category_id')) {
            $query->where('participant_category_id', $request->participant_category_id);
        }

        if ($request->filled('bird_type')) {
            $query->where('bird_type', $request->bird_type);
        }

        return response()->json([
            'success' => true,
            'data' => PricingItemResource::collection($query->get()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'participant_category_id' => ['required', 'exists:participant_categories,id'],
            'bird_type' => ['required', 'in:early,late'],
            'includes_symposium' => ['required',' boolean'],
            'workshop_count' => ['required', 'integer', 'min:0', 'max:2'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            PricingItem::create($data);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
            // return response()->json([
            //     'message' => 'Pricing untuk kombinasi ini sudah ada.',
            // ], 422);
        }

        return response()->json(['message' => 'Pricing berhasil ditambahkan'], 201);
    }

    public function update(Request $request, PricingItem $pricingItem)
    {
        $data = $request->validate([
            'participant_category_id' => ['required', 'exists:participant_categories,id'],
            'bird_type' => ['required', 'in:early,late'],
            'includes_symposium' => ['required','boolean'],
            'workshop_count' => ['required', 'integer', 'min:0', 'max:2'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $pricingItem->update($data);

        return response()->json(['message' => 'Pricing berhasil diperbarui']);
    }

    public function destroy(PricingItem $pricingItem)
    {
        $pricingItem->delete();

        return response()->json(['message' => 'Pricing berhasil dihapus']);
    }

    public function summary(Request $request)
    {
        // Optional: filter event_id kalau satu pricing dipakai multi event
        $eventId = $request->get('event_id');

        $query = PricingItem::with(['participantCategory'])
            ->withCount([
                // total semua registrasi
                'registrations as registrations_total_count' => function ($q) use ($eventId) {
                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },

                // EARLY BIRD
                'registrations as registrations_early_total_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'early');
                    });

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },
                'registrations as registrations_early_paid_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'early');
                    })->where('payment_step', 'paid');

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },
                'registrations as registrations_early_unpaid_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'early');
                    })->where('payment_step', '!=', 'paid');

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },

                // LATE BIRD
                'registrations as registrations_late_total_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'late');
                    });

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },
                'registrations as registrations_late_paid_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'late');
                    })->where('payment_step', 'paid');

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },
                'registrations as registrations_late_unpaid_count' => function ($q) use ($eventId) {
                    $q->whereHas('pricingItem', function ($q2) {
                        $q2->where('bird_type', 'late');
                    })->where('payment_step', '!=', 'paid');

                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                },
            ])
            ->orderBy('participant_category_id')
            ->orderByRaw("
                CASE
                    WHEN includes_symposium = 1 AND workshop_count = 0 THEN 1
                    WHEN includes_symposium = 1 AND workshop_count = 1 THEN 2
                    WHEN includes_symposium = 1 AND workshop_count = 2 THEN 3
                    WHEN includes_symposium = 0 AND workshop_count = 1 THEN 4
                    ELSE 99
                END
            ");

        $items = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'participant_category_id' => $item->participant_category_id,
                'participant_category_name' => $item->participantCategory?->name,
                'bird_type' => $item->bird_type,
                'package_label' => $item->package_label, // accessor dari model
                'price' => $item->price,

                // TOTAL
                'total_registrations' => (int) $item->registrations_total_count,

                // EARLY
                'early_total' => (int) $item->registrations_early_total_count,
                'early_paid' => (int) $item->registrations_early_paid_count,
                'early_unpaid' => (int) $item->registrations_early_unpaid_count,

                // LATE
                'late_total' => (int) $item->registrations_late_total_count,
                'late_paid' => (int) $item->registrations_late_paid_count,
                'late_unpaid' => (int) $item->registrations_late_unpaid_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

}
