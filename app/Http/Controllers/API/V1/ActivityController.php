<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $eventId  = $request->get('event_id');
        $category = $request->get('category');
        $perPage  = (int) ($request->get('per_page') ?? 10);

        $query = Activity::with('topics', 'event')
            ->orderBy('category');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($category) {
            $query->where('category', $category); // ⬅️ FILTER CATEGORY
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'    => ['required', 'exists:events,id'],
            'category'    => ['required', 'in:plenary,symposium,workshop,jeopardy,poster'],
            'code'        => ['nullable', 'string', 'max:20'],
            'title'       => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'is_paid'     => ['boolean'],
            'quota'       => ['nullable', 'integer', 'min:0'],
        ]);

        $activity = Activity::create($data);

        return response()->json([
            'message' => 'Activity berhasil ditambahkan.',
            'data'    => $activity,
        ], 201);
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'category'    => ['required', 'in:plenary,symposium,workshop,jeopardy,poster'],
            'code'        => ['nullable', 'string', 'max:20'],
            'title'       => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'is_paid'     => ['boolean'],
            'quota'       => ['nullable', 'integer', 'min:0'],
        ]);

        $activity->update($data);

        return response()->json([
            'message' => 'Activity berhasil diperbarui.',
            'data'    => $activity,
        ]);
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return response()->json([
            'message' => 'Activity berhasil dihapus.',
        ]);
    }
}
