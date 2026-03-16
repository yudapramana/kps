<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventStatistic;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsController extends Controller
{

    /*
    |------------------------------------------------
    | GET DASHBOARD STATISTICS
    |------------------------------------------------
    */
    public function show()
    {
        $event = Event::where('is_active', true)->firstOrFail();

        $stats = EventStatistic::firstOrCreate(
            ['event_id' => $event->id]
        );

        return response()->json($stats);
    }


    /*
    |------------------------------------------------
    | REFRESH STATISTICS
    |------------------------------------------------
    */
    public function refresh()
    {
        $event = Event::where('is_active', true)->firstOrFail();

        /*
        |----------------------------------------
        | PARTICIPANT
        |----------------------------------------
        */
        $totalParticipants = Participant::count();

        /*
        |----------------------------------------
        | REGISTRATION
        |----------------------------------------
        */
        $paidParticipants = Registration::where('status', 'paid')->count();

        $unpaidParticipants = $totalParticipants - $paidParticipants;

        /*
        |----------------------------------------
        | ACTIVITY REGISTRATION
        |----------------------------------------
        */
        $alreadyRegistered = DB::table('registration_items')
            ->join('registrations', 'registration_items.registration_id', '=', 'registrations.id')
            ->where('registrations.status', 'paid')
            ->distinct('registrations.participant_id')
            ->count('registrations.participant_id');

        $notYetRegistered = $paidParticipants - $alreadyRegistered;

        /*
        |----------------------------------------
        | SAVE
        |----------------------------------------
        */
        $stats = EventStatistic::updateOrCreate(
            ['event_id' => $event->id],
            [
                'total_participants' => $totalParticipants,
                'paid_participants' => $paidParticipants,
                'unpaid_participants' => $unpaidParticipants,

                'total_registrations' => $paidParticipants,
                'already_registered' => $alreadyRegistered,
                'not_yet_registered' => $notYetRegistered,

                'calculated_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Statistics refreshed',
            'data' => $stats
        ]);
    }
}