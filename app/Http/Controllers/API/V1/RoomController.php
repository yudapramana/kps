<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * GET /api/v1/rooms
     */
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $eventId = $request->get('event_id');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Room::query()
            ->orderBy('category')
            ->orderBy('name');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /**
     * POST /api/v1/rooms
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'name'     => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:symposium,workshop,jeopardy'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ]);

        $room = Room::create($data);

        return response()->json([
            'message' => 'Ruangan berhasil ditambahkan.',
            'data'    => $room,
        ], 201);
    }

    /**
     * PUT /api/v1/rooms/{room}
     */
    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:symposium,workshop,jeopardy'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ]);

        $room->update($data);

        return response()->json([
            'message' => 'Ruangan berhasil diperbarui.',
            'data'    => $room,
        ]);
    }

    /**
     * DELETE /api/v1/rooms/{room}
     */
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'message' => 'Ruangan berhasil dihapus.',
        ]);
    }
}
