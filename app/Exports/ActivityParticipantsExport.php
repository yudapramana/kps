<?php

namespace App\Exports;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ActivityParticipantsExport extends StringValueBinder implements
    FromCollection,
    WithHeadings,
    WithCustomValueBinder,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(): Collection
    {
        $search = $this->request->get('search');
        $activityFilter = $this->request->get('activity_filter'); // "symposium" atau ID workshop
        $participantCategoryId = $this->request->get('participant_category_id');
        $packageFilter = $this->request->get('package_filter'); // symposium, symposium_1_ws, symposium_2_ws, ws_nurse

        $query = Participant::with([
            'participantCategory',
            'registration.pricingItem',
            'registrationItems.activity',
        ])
            ->whereHas('registrationItems.activity', function ($q) {
                $q->whereIn('category', ['workshop', 'symposium']);
            })
            ->whereHas('registration', function ($q) {
                $q->where('payment_step', 'paid');
            });

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

        $participants = $query->orderBy('full_name')->get();

        $rows = [];
        $no = 1;

        foreach ($participants as $p) {
            $allRegistrationItems = $p->registrationItems
                ->filter(function ($regItem) {
                    return $regItem->activity
                        && in_array($regItem->activity->category, ['workshop', 'symposium']);
                })
                ->values();

            $packageLabel = optional(
                $p->registration?->pricingItem
            )->package_label ?? 'No Package Selected';

            $symposiumActivities = $allRegistrationItems
                ->filter(fn($regItem) => $regItem->activity && $regItem->activity->category === 'symposium');

            $workshopActivities = $allRegistrationItems
                ->filter(fn($regItem) => $regItem->activity && $regItem->activity->category === 'workshop')
                ->values();

            $includesSymposium = $symposiumActivities->isNotEmpty() ? 'Ya' : 'Tidak';

            $workshop1Title = $workshopActivities->get(0)
                ? $workshopActivities->get(0)->activity->title
                : '-';

            $workshop2Title = $workshopActivities->get(1)
                ? $workshopActivities->get(1)->activity->title
                : '-';

            $rows[] = [
                $no++,
                $p->full_name,
                (string) $p->nik,
                $p->email,
                (string) $p->mobile_phone,
                $p->institution,
                $p->participantCategory?->name,
                $packageLabel,
                $includesSymposium,
                $workshop1Title,
                $workshop2Title,
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Name',
            'NIK',
            'Email',
            'Phone',
            'Institution',
            'Category',
            'Package',
            'Includes Symposium',
            'Workshop 1',
            'Workshop 2',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Freeze Header
                $sheet->freezePane('A2');

                // Auto Filter (sesuai jumlah kolom A–K)
                $sheet->setAutoFilter('A1:K1');

                // Bisa tambahkan styling tambahan di sini jika perlu
            },
        ];
    }
}