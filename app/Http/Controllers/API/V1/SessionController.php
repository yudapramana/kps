<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\EventDay;
use App\Models\Room;
use App\Models\Activity;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $eventDay = EventDay::with('event')->findOrFail($request->event_day_id);

        $query = Session::with(['room', 'activity'])
            ->where('event_day_id', $eventDay->id)
            ->orderBy('start_time');

        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'event'      => $eventDay->event,
                'event_day'  => $eventDay,
                'event_days' => EventDay::where('event_id', $eventDay->event_id)
                                    ->orderBy('date')->get(),
                'sessions'   => $query->get(),
                'rooms'      => Room::where('event_id', $eventDay->event_id)->orderBy('name')->get(),
                'activities' => Activity::where('event_id', $eventDay->event_id)->orderBy('title')->get(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_day_id' => ['required', 'exists:event_days,id'],
            'room_id'      => ['required', 'exists:rooms,id'],
            'activity_id'  => ['required', 'exists:activities,id'],
            'start_time'   => ['required'],
            'end_time'     => ['required', 'after:start_time'],
        ]);

        Session::create($data);

        return response()->json(['message' => 'Session berhasil ditambahkan'], 201);
    }

    public function update(Request $request, Session $session)
    {
        $data = $request->validate([
            'room_id'     => ['required', 'exists:rooms,id'],
            'activity_id' => ['required', 'exists:activities,id'],
            'start_time'  => ['required'],
            'end_time'    => ['required', 'after:start_time'],
        ]);

        $session->update($data);

        return response()->json(['message' => 'Session berhasil diperbarui']);
    }

    public function destroy(Session $session)
    {
        $session->delete();
        return response()->json(['message' => 'Session berhasil dihapus']);
    }
}
