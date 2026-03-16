<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventStatistic;
use App\Models\Participant;
use App\Models\Registration;
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
        | TOTAL PARTICIPANTS
        |----------------------------------------
        */
        $totalParticipants = Participant::count();

        /*
        |----------------------------------------
        | REGISTRATION SUMMARY
        |----------------------------------------
        */

        // participant yang sudah punya registration
        $alreadyRegistered = Registration::whereNotNull('participant_id')->distinct('participant_id')
            ->count('participant_id');

        // participant yang belum punya registration
        $notYetRegistered = $totalParticipants - $alreadyRegistered;


        /*
        |----------------------------------------
        | PARTICIPANT SUMMARY
        |----------------------------------------
        */

        // paid
        $paidParticipants = Registration::whereNotNull('participant_id')->where('status', 'paid')
            ->count();

        // unpaid
        $unpaidParticipants = Registration::whereNotNull('participant_id')->where('status', '!=', 'paid')
            ->count();

        // total registrasi
        $totalRegistrations = Registration::whereNotNull('participant_id')->count();


        /*
        |----------------------------------------
        | SAVE STATISTICS
        |----------------------------------------
        */
        $stats = EventStatistic::updateOrCreate(
            ['event_id' => $event->id],
            [
                'total_participants' => $paidParticipants + $unpaidParticipants,

                'paid_participants' => $paidParticipants,
                'unpaid_participants' => $unpaidParticipants,

                'total_registrations' => $alreadyRegistered + $notYetRegistered,
                // 'total_registrations' => $totalRegistrations,
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