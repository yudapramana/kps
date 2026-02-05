<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceDashboardController extends Controller
{
    /**
     * GET /api/v1/finance/dashboard
     */
    public function index(Request $request)
    {
        // ===============================
        // SUMMARY
        // ===============================
        $totalPaid = DB::table('payments')
            ->where('status', 'verified')
            ->sum('amount');

        $totalPending = DB::table('payments')
            ->whereIn('status', ['pending', 'rejected'])
            ->sum('amount');

        // ===============================
        // DAILY REVENUE
        // ===============================
        $daily = DB::table('payments')
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->where('status', 'verified')
            ->whereNotNull('paid_at')
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('date')
            ->get();

        // ===============================
        // BY BANK (bank nullable)
        // ===============================
        $byBank = DB::table('payments')
            ->join('registrations', 'registrations.id', '=', 'payments.registration_id')
            ->leftJoin('banks', 'banks.id', '=', 'registrations.bank_id')
            ->where('payments.status', 'verified')
            ->groupBy('banks.name')
            ->selectRaw("
                COALESCE(banks.name, 'Tanpa Bank') as name,
                SUM(payments.amount) as total
            ")
            ->get();

        // ===============================
        // BY PACKAGE (MATCH packageLabel())
        // ===============================
        $byPackage = DB::table('payments')
            ->join('registrations', 'registrations.id', '=', 'payments.registration_id')
            ->join('pricing_items', 'pricing_items.id', '=', 'registrations.pricing_item_id')
            ->where('payments.status', 'verified')
            ->groupBy(
                'pricing_items.includes_symposium',
                'pricing_items.workshop_count'
            )
            ->selectRaw("
                CASE
                    WHEN pricing_items.includes_symposium = 0
                        THEN 'Workshop Only'
                    WHEN pricing_items.workshop_count = 0
                        THEN 'Symposium'
                    ELSE
                        CONCAT('Symposium + ', pricing_items.workshop_count, ' Workshop')
                END as name,
                SUM(payments.amount) as total
            ")
            ->get();

        // ===============================
        // BY PARTICIPANT CATEGORY
        // ===============================
        $byCategory = DB::table('payments')
            ->join('registrations', 'registrations.id', '=', 'payments.registration_id')
            ->join('participants', 'participants.id', '=', 'registrations.participant_id')
            ->join('participant_categories', 'participant_categories.id', '=', 'participants.participant_category_id')
            ->where('payments.status', 'verified')
            ->groupBy('participant_categories.name')
            ->selectRaw('participant_categories.name as name, SUM(payments.amount) as total')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_paid'    => (float) $totalPaid,
                    'total_pending' => (float) $totalPending,
                ],
                'daily'       => $daily,
                'by_bank'     => $byBank,
                'by_package'  => $byPackage,
                'by_category' => $byCategory,
            ],
        ]);
    }
}
