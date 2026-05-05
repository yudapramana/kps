<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityStatistic;
use App\Models\Event;
use App\Models\EventStatistic;
use App\Models\PackageStatistic;
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

        $activityStatistics = ActivityStatistic::query()
            ->leftJoin('activities', 'activity_statistics.activity_id', '=', 'activities.id')
            ->where('activity_statistics.event_id', $event->id)
            ->orderByRaw("
                CASE
                    WHEN activity_statistics.activity_type = 'symposium' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('activities.title')
            ->get([
                'activity_statistics.id',
                'activity_statistics.event_id',
                'activity_statistics.activity_id',
                'activity_statistics.activity_type',
                'activity_statistics.paid_participants',
                'activity_statistics.calculated_at',
                'activities.title as activity_title',
            ]);

        $packageStatistics = PackageStatistic::where('event_id', $event->id)
            ->orderBy('workshop_count')
            ->get();

        return response()->json([
            'event_statistics' => $stats,
            'activity_statistics' => $activityStatistics,
            'package_statistics' => $packageStatistics,
        ]);
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
        $alreadyRegistered = Registration::whereNotNull('participant_id')
            ->distinct('participant_id')
            ->count('participant_id');

        $notYetRegistered = $totalParticipants - $alreadyRegistered;

        /*
        |----------------------------------------
        | PARTICIPANT SUMMARY
        |----------------------------------------
        */
        $paidParticipants = Registration::whereNotNull('participant_id')
            ->where('status', 'paid')
            ->count();

        $unpaidParticipants = Registration::whereNotNull('participant_id')
            ->where('status', '!=', 'paid')
            ->count();

        $totalRegistrations = Registration::whereNotNull('participant_id')->count();

        /*
        |----------------------------------------
        | SAVE EVENT STATISTICS
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

        /*
        |----------------------------------------
        | REFRESH ACTIVITY STATISTICS
        |----------------------------------------
        */
        ActivityStatistic::where('event_id', $event->id)->delete();

        // Symposium digabung jadi satu
        $symposiumPaidParticipants = DB::table('registration_items')
            ->join('registrations', 'registration_items.registration_id', '=', 'registrations.id')
            ->join('activities', 'registration_items.activity_id', '=', 'activities.id')
            ->where('registrations.status', 'paid')
            ->whereNotNull('registrations.participant_id')
            ->where('activities.category', 'symposium')
            ->distinct()
            ->count('registrations.participant_id');

        ActivityStatistic::create([
            'event_id' => $event->id,
            'activity_id' => null,
            'activity_type' => 'symposium',
            'paid_participants' => $symposiumPaidParticipants,
            'calculated_at' => now(),
        ]);

        // Workshop per activity
        $workshopStatistics = DB::table('registration_items')
            ->join('registrations', 'registration_items.registration_id', '=', 'registrations.id')
            ->join('activities', 'registration_items.activity_id', '=', 'activities.id')
            ->where('registrations.status', 'paid')
            ->whereNotNull('registrations.participant_id')
            ->where('activities.category', 'workshop')
            ->select(
                'registration_items.activity_id',
                DB::raw('COUNT(DISTINCT registrations.participant_id) as paid_participants')
            )
            ->groupBy('registration_items.activity_id')
            ->get();

        foreach ($workshopStatistics as $workshop) {
            ActivityStatistic::create([
                'event_id' => $event->id,
                'activity_id' => $workshop->activity_id,
                'activity_type' => 'workshop',
                'paid_participants' => $workshop->paid_participants,
                'calculated_at' => now(),
            ]);
        }

        /*
        |----------------------------------------
        | REFRESH PACKAGE STATISTICS
        |----------------------------------------
        */
        PackageStatistic::where('event_id', $event->id)->delete();

        $packageRows = DB::table('registrations')
            ->join('pricing_items', 'registrations.pricing_item_id', '=', 'pricing_items.id')
            ->where('registrations.status', 'paid')
            ->whereNotNull('registrations.participant_id')
            ->select(
                'pricing_items.includes_symposium',
                'pricing_items.workshop_count',
                DB::raw('COUNT(DISTINCT registrations.participant_id) as paid_participants')
            )
            ->groupBy('pricing_items.includes_symposium', 'pricing_items.workshop_count')
            ->get();

        foreach ($packageRows as $row) {
            $includesSymposium = (bool) $row->includes_symposium;
            $workshopCount = (int) $row->workshop_count;

            if (!$includesSymposium && $workshopCount === 1) {
                $packageCode = 'ws_nurse';
                $packageLabel = 'Workshop for Nurse';
            } else {
                if ($includesSymposium && $workshopCount === 0) {
                    $packageCode = 'symposium';
                } elseif ($includesSymposium && $workshopCount === 1) {
                    $packageCode = 'symposium_1_ws';
                } elseif ($includesSymposium && $workshopCount === 2) {
                    $packageCode = 'symposium_2_ws';
                } else {
                    $packageCode = 'symposium_' . $workshopCount . '_ws';
                }

                $packageLabel = 'Symposium';

                if ($workshopCount > 0) {
                    $workshopText = $workshopCount === 1
                        ? '1 Workshop'
                        : $workshopCount . ' Workshops';

                    $packageLabel .= ' + ' . $workshopText;
                }
            }

            PackageStatistic::create([
                'event_id' => $event->id,
                'package_code' => $packageCode,
                'package_label' => $packageLabel,
                'includes_symposium' => $includesSymposium,
                'workshop_count' => $workshopCount,
                'paid_participants' => $row->paid_participants,
                'calculated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Statistics refreshed',
            'data' => $stats
        ]);
    }
}