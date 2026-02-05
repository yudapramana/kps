<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ServiceAccount;

class EventController extends Controller
{
    /**
     * GET /api/v1/events
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
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'theme'                   => ['nullable', 'string'],
            'description'             => ['nullable', 'string'],

            'start_date'              => ['required', 'date'],
            'end_date'                => ['required', 'date', 'after_or_equal:start_date'],
            'early_bird_end_date'     => ['nullable', 'date'],

            // SUBMISSION TIMELINE
            'submission_open_at'      => ['nullable', 'date'],
            'submission_deadline_at'  => ['nullable', 'date', 'after_or_equal:submission_open_at'],
            'notification_date'       => ['nullable', 'date'],
            'submission_close_at'     => ['nullable', 'date'],

            // CONTROL
            'submission_is_active'    => ['boolean'],

            'location'                => ['nullable', 'string'],
            'venue'                   => ['nullable', 'string'],
            'is_active'               => ['boolean'],
        ]);

        // slug otomatis
        $data['slug'] = Str::slug($data['name']);

        $event = Event::create($data);

        return response()->json([
            'message' => 'Event berhasil ditambahkan.',
            'data'    => $event,
        ], 201);
    }

    /**
     * PUT /api/v1/events/{event}
     */
    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'theme'                   => ['nullable', 'string'],
            'description'             => ['nullable', 'string'],

            'start_date'              => ['required', 'date'],
            'end_date'                => ['required', 'date', 'after_or_equal:start_date'],
            'early_bird_end_date'     => ['nullable', 'date'],

            // SUBMISSION TIMELINE
            'submission_open_at'      => ['nullable', 'date'],
            'submission_deadline_at'  => ['nullable', 'date', 'after_or_equal:submission_open_at'],
            'notification_date'       => ['nullable', 'date'],
            'submission_close_at'     => ['nullable', 'date'],

            // CONTROL
            'submission_is_active'    => ['boolean'],

            'location'                => ['nullable', 'string'],
            'venue'                   => ['nullable', 'string'],
            'is_active'               => ['boolean'],
        ]);

        // update slug kalau nama berubah
        if ($data['name'] !== $event->name) {
            $data['slug'] = Str::slug($data['name']);
        }

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
     */
    public function getEventActive()
    {
        $event = Event::where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        if (! $event) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $service = ServiceAccount::where('name', 'Customer API')->first();

        $event->service = $service
            ? [
                'name'  => $service->name,
                'token' => $service->token,
            ]
            : null;

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }
}
