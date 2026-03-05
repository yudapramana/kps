<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ParticipantCategory;
use Illuminate\Http\Request;
use App\Exports\ParticipantsExport;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantController extends Controller
{
    /**
     * GET /api/v1/participants
     * List + search + filter category + pagination (READ ONLY)
     */
    public function index(Request $request)
    {
        $search     = $request->get('search');
        $categoryId = $request->get('participant_category_id');
        $isPaid     = $request->get('is_paid');
        $perPage    = (int) ($request->get('per_page') ?? 10);

        $query = Participant::with('participantCategory')
            ->orderBy('full_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('participant_category_id', $categoryId);
        }

        // PAYMENT FILTER
        if ($isPaid !== null && $isPaid !== '') {

            // SUDAH BAYAR
            if ($isPaid == 1) {
                $query->whereHas('registrations', function ($q) {
                    $q->where('payment_step', 'paid');
                });
            }

            // BELUM BAYAR
            if ($isPaid == 0) {
                $query->where(function ($q) {

                    // tidak punya registration
                    $q->whereDoesntHave('registrations')

                    // atau punya tapi belum paid
                    ->orWhereHas('registrations', function ($q2) {
                        $q2->where('payment_step', '!=', 'paid');
                    });

                });
            }
        }

        return response()->json([
            'success'    => true,
            'data'       => $query->paginate($perPage),
            'categories' => ParticipantCategory::orderBy('name')->get(),
        ]);
    }


    

    public function export(Request $request)
    {
        return Excel::download(
            new ParticipantsExport($request),
            'participants_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
