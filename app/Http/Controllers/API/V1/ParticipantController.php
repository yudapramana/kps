<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ParticipantCategory;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    /**
     * GET /api/v1/participants
     * List + search + filter category + pagination (READ ONLY)
     */
    public function index(Request $request)
    {
        $search     = $request->get('search');
        $categoryId = $request->get('participant_category_id');
        $perPage    = (int) ($request->get('per_page') ?? 10);

        $query = Participant::with('participantCategory')
            ->orderBy('full_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('participant_category_id', $categoryId);
        }

        return response()->json([
            'success'    => true,
            'data'       => $query->paginate($perPage),
            'categories' => ParticipantCategory::orderBy('name')->get(),
        ]);
    }
}
