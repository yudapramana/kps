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

        $query = Participant::with([
            'participantCategory',
            'registration.pricingItem'
        ])->orderBy('created_at');

        /*
        |--------------------------------------------------
        | SEARCH
        |--------------------------------------------------
        */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
                ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------
        | FILTER CATEGORY
        |--------------------------------------------------
        */
        if ($categoryId) {
            $query->where('participant_category_id', $categoryId);
        }

        /*
        |--------------------------------------------------
        | FILTER PAYMENT
        |--------------------------------------------------
        */
        if ($isPaid !== null && $isPaid !== '') {

            // SUDAH BAYAR
            if ($isPaid == 1) {
                $query->whereHas('registration', function ($q) {
                    $q->where('payment_step', 'paid');
                });
            }

            // BELUM BAYAR
            if ($isPaid == 0) {
                $query->where(function ($q) {

                    // tidak punya registration
                    $q->whereDoesntHave('registration')

                    // atau punya tapi belum paid
                    ->orWhereHas('registration', function ($q2) {
                        $q2->where('payment_step', '!=', 'paid');
                    });

                });
            }
        }

        /*
        |--------------------------------------------------
        | PAGINATION
        |--------------------------------------------------
        */
        $data = $query->paginate($perPage);

        /*
        |--------------------------------------------------
        | TRANSFORM DATA (ADD PACKAGE LABEL)
        |--------------------------------------------------
        */
        $data->getCollection()->transform(function ($item) {

            $item->package_label = optional(
                $item->registration?->pricingItem
            )->package_label;

            // OPTIONAL: langsung expose payment biar FE lebih simple
            $item->is_paid = $item->registration?->payment_step === 'paid';

            return $item;
        });

        /*
        |--------------------------------------------------
        | RESPONSE
        |--------------------------------------------------
        */
        return response()->json([
            'success'    => true,
            'data'       => $data,
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
