<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityPanelist;
use Illuminate\Http\Request;

class ActivityPanelistController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id'=>['required','exists:activities,id'],
            'name'=>['required','string']
        ]);

        $panelist = ActivityPanelist::create($data);

        return response()->json([
            'message'=>'Panelist berhasil ditambahkan',
            'data'=>$panelist
        ],201);
    }

    public function update(Request $request, ActivityPanelist $activityPanelist)
    {
        $data = $request->validate([
            'name'=>['required','string']
        ]);

        $activityPanelist->update($data);

        return response()->json([
            'message'=>'Panelist diperbarui',
            'data'=>$activityPanelist
        ]);
    }

    public function destroy(ActivityPanelist $activityPanelist)
    {
        $activityPanelist->delete();

        return response()->json([
            'message'=>'Panelist dihapus'
        ]);
    }
}