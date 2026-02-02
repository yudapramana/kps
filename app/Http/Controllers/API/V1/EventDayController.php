<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDay;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EventDayController extends Controller
{
    /**
     * GET /api/v1/event-days
     * List + pagination + filter by event
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->get('per_page') ?? 10);
        $eventId = $request->get('event_id');

        $query = EventDay::with('event')
            ->orderBy('date');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }


    /**
     * POST /api/v1/event-days
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'date'     => ['required', 'date'],
            'label'    => ['nullable', 'string'],
        ]);

        $day = EventDay::create($data);

        return response()->json([
            'message' => 'Hari event berhasil ditambahkan.',
            'data'    => $day,
        ], 201);
    }

    /**
     * PUT /api/v1/event-days/{eventDay}
     */
    public function update(Request $request, EventDay $eventDay)
    {
        $data = $request->validate([
            'date'  => ['required', 'date'],
            'label' => ['nullable', 'string'],
        ]);

        $eventDay->update($data);

        return response()->json([
            'message' => 'Hari event berhasil diperbarui.',
            'data'    => $eventDay,
        ]);
    }

    /**
     * DELETE /api/v1/event-days/{eventDay}
     */
    public function destroy(EventDay $eventDay)
    {
        $eventDay->delete();

        return response()->json([
            'message' => 'Hari event berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/v1/event-days/generate/{event}
     * Generate otomatis dari start_date → end_date
     */
    public function generateByEvent(Event $event)
    {
        // hapus dulu biar tidak double
        EventDay::where('event_id', $event->id)->delete();

        $period = CarbonPeriod::create(
            Carbon::parse($event->start_date),
            Carbon::parse($event->end_date)
        );

        $counter = 1;

        foreach ($period as $date) {
            EventDay::create([
                'event_id' => $event->id,
                'date'     => $date->toDateString(),
                'label'    => 'Hari-' . $counter,
            ]);
            $counter++;
        }

        return response()->json([
            'message' => 'Hari event berhasil digenerate otomatis.',
        ]);
    }
}
