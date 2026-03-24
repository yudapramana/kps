<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivitySpeaker;
use Illuminate\Http\Request;

class ActivitySpeakerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id' => ['required','exists:activities,id'],
            'name' => ['required','string']
        ]);

        $speaker = ActivitySpeaker::create($data);

        return response()->json([
            'message'=>'Speaker berhasil ditambahkan',
            'data'=>$speaker
        ],201);
    }

    public function update(Request $request, ActivitySpeaker $activitySpeaker)
    {
        $data = $request->validate([
            'name'=>['required','string']
        ]);

        $activitySpeaker->update($data);

        return response()->json([
            'message'=>'Speaker diperbarui',
            'data'=>$activitySpeaker
        ]);
    }

    public function destroy(ActivitySpeaker $activitySpeaker)
    {
        $activitySpeaker->delete();

        return response()->json([
            'message'=>'Speaker dihapus'
        ]);
    }
}
