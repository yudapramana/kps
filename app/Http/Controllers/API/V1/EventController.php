<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * GET /api/v1/events
     * List + search + pagination
     */
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Event::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('theme', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('created_at');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /**
     * POST /api/v1/events
     * Store
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'theme'                => ['nullable', 'string'],
            'description'          => ['nullable', 'string'],
            'early_bird_end_date'  => ['nullable', 'date'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['required', 'date', 'after_or_equal:start_date'],
            'location'             => ['nullable', 'string'],
            'venue'                => ['nullable', 'string'],
            'is_active'             => ['boolean'],
        ]);

        $event = Event::create($data);

        return response()->json([
            'message' => 'Event berhasil ditambahkan.',
            'data'    => $event,
        ], 201);
    }

    /**
     * PUT /api/v1/events/{event}
     * Update
     */
    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'theme'                => ['nullable', 'string'],
            'description'          => ['nullable', 'string'],
            'early_bird_end_date'  => ['nullable', 'date'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['required', 'date', 'after_or_equal:start_date'],
            'location'             => ['nullable', 'string'],
            'venue'                => ['nullable', 'string'],
            'is_active'             => ['boolean'],
        ]);

        $event->update($data);

        return response()->json([
            'message' => 'Event berhasil diperbarui.',
            'data'    => $event,
        ]);
    }

    /**
     * DELETE /api/v1/events/{event}
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Event berhasil dihapus.',
        ]);
    }

    /**
     * GET /api/v1/events/active
     * Ambil 1 event yang sedang aktif
     */
    public function getEventActive()
    {
        $event = Event::where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

}
