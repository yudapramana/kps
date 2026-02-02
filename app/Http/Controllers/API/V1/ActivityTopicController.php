<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityTopic;
use Illuminate\Http\Request;

class ActivityTopicController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'title'       => ['required', 'string'],
            'type'        => ['required', 'in:lecture,case,video,discussion'],
            'order'       => ['required', 'integer', 'min:1'],
        ]);

        $topic = ActivityTopic::create($data);

        return response()->json([
            'message' => 'Topik berhasil ditambahkan.',
            'data'    => $topic,
        ], 201);
    }

    public function update(Request $request, ActivityTopic $activityTopic)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'type'  => ['required', 'in:lecture,case,video,discussion'],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        $activityTopic->update($data);

        return response()->json([
            'message' => 'Topik berhasil diperbarui.',
            'data'    => $activityTopic,
        ]);
    }

    public function destroy(ActivityTopic $activityTopic)
    {
        $activityTopic->delete();

        return response()->json([
            'message' => 'Topik berhasil dihapus.',
        ]);
    }
}
