<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivitySponsor;
use Illuminate\Http\Request;

class ActivitySponsorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id'=>['required','exists:activities,id'],
            'name'=>['required','string'],
            'logo_url'=>['nullable','string']
        ]);

        $sponsor = ActivitySponsor::create($data);

        return response()->json([
            'message'=>'Sponsor ditambahkan',
            'data'=>$sponsor
        ],201);
    }

    public function update(Request $request, ActivitySponsor $activitySponsor)
    {
        $data = $request->validate([
            'name'=>['required','string'],
            'logo_url'=>['nullable','string']
        ]);

        $activitySponsor->update($data);

        return response()->json([
            'message'=>'Sponsor diperbarui',
            'data'=>$activitySponsor
        ]);
    }

    public function destroy(ActivitySponsor $activitySponsor)
    {
        $activitySponsor->delete();

        return response()->json([
            'message'=>'Sponsor dihapus'
        ]);
    }
}
