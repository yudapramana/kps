<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Participant;
use App\Models\ParticipantCategory;
use Illuminate\Http\Request;
use App\Exports\ActivityParticipantsExport;
use Maatwebsite\Excel\Facades\Excel;

class RegisterParticipantController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $activityFilter = $request->get('activity_filter'); // "symposium" atau ID workshop
        $participantCategoryId = $request->get('participant_category_id');
        $packageFilter = $request->get('package_filter'); // symposium, symposium_1_ws, symposium_2_ws, ws_nurse
        $paymentStep = $request->get('payment_step'); // choose_bank, waiting_transfer, waiting_verification, paid
        $perPage = (int) ($request->get('per_page') ?? 10);

        $allowedPaymentSteps = [
            'choose_bank',
            'waiting_transfer',
            'waiting_verification',
            'paid',
        ];

        $query = Participant::with([
            'participantCategory',
            'registration.pricingItem',
            'registrationItems.activity',
        ])
            ->whereHas('registrationItems.activity', function ($q) {
                $q->whereIn('category', ['workshop', 'symposium']);
            })
            ->orderBy('created_at', 'desc');

        // FILTER ACTIVITY
        if ($activityFilter) {
            if ($activityFilter === 'symposium') {
                $query->whereHas('registrationItems.activity', function ($q) {
                    $q->where('category', 'symposium');
                });
            } else {
                $query->whereHas('registrationItems.activity', function ($q) use ($activityFilter) {
                    $q->where('id', $activityFilter)
                        ->where('category', 'workshop');
                });
            }
        }

        // FILTER PARTICIPANT CATEGORY
        if ($participantCategoryId) {
            $query->where('participant_category_id', $participantCategoryId);
        }

        // FILTER PAYMENT STEP
        if ($paymentStep && in_array($paymentStep, $allowedPaymentSteps)) {
            $query->whereHas('registration', function ($q) use ($paymentStep) {
                $q->where('payment_step', $paymentStep);
            });
        }

        // FILTER PACKAGE
        if ($packageFilter) {
            $query->whereHas('registration.pricingItem', function ($q) use ($packageFilter) {
                if ($packageFilter === 'symposium') {
                    $q->where('includes_symposium', 1)
                        ->where('workshop_count', 0);
                }

                if ($packageFilter === 'symposium_1_ws') {
                    $q->where('includes_symposium', 1)
                        ->where('workshop_count', 1);
                }

                if ($packageFilter === 'symposium_2_ws') {
                    $q->where('includes_symposium', 1)
                        ->where('workshop_count', '>=', 2);
                }

                if ($packageFilter === 'ws_nurse') {
                    $q->where('includes_symposium', 0)
                        ->where('workshop_count', 1);
                }
            });
        }

        // SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            $allRegistrationItems = $item->registrationItems
                ->filter(function ($regItem) {
                    return $regItem->activity
                        && in_array($regItem->activity->category, ['workshop', 'symposium']);
                })
                ->values();

            $item->package_label = optional($item->registration?->pricingItem)->package_label;

            $item->payment_step = $item->registration?->payment_step;
            $item->payment_status = $item->registration?->status;
            $item->is_paid = $item->registration?->payment_step === 'paid';

            $symposiumActivities = $allRegistrationItems
                ->filter(fn ($regItem) => $regItem->activity && $regItem->activity->category === 'symposium');

            $workshopActivities = $allRegistrationItems
                ->filter(fn ($regItem) => $regItem->activity && $regItem->activity->category === 'workshop')
                ->values();

            $item->includes_symposium = $symposiumActivities->isNotEmpty();

            $item->workshop_1 = $workshopActivities->get(0)
                ? [
                    'id' => $workshopActivities->get(0)->activity->id,
                    'title' => $workshopActivities->get(0)->activity->title,
                ]
                : null;

            $item->workshop_2 = $workshopActivities->get(1)
                ? [
                    'id' => $workshopActivities->get(1)->activity->id,
                    'title' => $workshopActivities->get(1)->activity->title,
                ]
                : null;

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filters' => [
                'payment_steps' => [
                    ['value' => 'choose_bank', 'label' => 'Choose Bank'],
                    ['value' => 'waiting_transfer', 'label' => 'Waiting Transfer'],
                    ['value' => 'waiting_verification', 'label' => 'Waiting Verification'],
                    ['value' => 'paid', 'label' => 'Paid'],
                ],
            ],
            'activities' => collect([
                [
                    'id' => 'symposium',
                    'title' => 'Symposium',
                    'category' => 'symposium',
                ],
            ])->merge(
                Activity::where('category', 'workshop')
                    ->orderBy('title')
                    ->get(['id', 'title', 'category'])
            )->values(),
            'participant_categories' => ParticipantCategory::orderBy('name')
                ->get(['id as participant_category_id', 'name']),
        ]);
    }

    public function export(Request $request)
    {
        $fileName = 'activity-participants-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ActivityParticipantsExport($request), $fileName);
    }
}