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
            'workshop_count' => ['required', 'integer', 'min:0', 'max:2'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['includes_symposium'] = true;

        try {
            PricingItem::create($data);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Pricing untuk kombinasi ini sudah ada.',
            ], 422);
        }

        return response()->json(['message' => 'Pricing berhasil ditambahkan'], 201);
    }

    public function update(Request $request, PricingItem $pricingItem)
    {
        $data = $request->validate([
            'participant_category_id' => ['required', 'exists:participant_categories,id'],
            'bird_type' => ['required', 'in:early,late'],
            'workshop_count' => ['required', 'integer', 'min:0', 'max:2'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['includes_symposium'] = true;

        $pricingItem->update($data);

        return response()->json(['message' => 'Pricing berhasil diperbarui']);
    }

    public function destroy(PricingItem $pricingItem)
    {
        $pricingItem->delete();

        return response()->json(['message' => 'Pricing berhasil dihapus']);
    }
}
