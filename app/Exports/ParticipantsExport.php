<?php

namespace App\Exports;

use App\Models\Participant;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ParticipantsExport extends StringValueBinder implements 
    FromCollection,
    WithHeadings,
    WithCustomValueBinder,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $search = $this->request->get('search');
        $categoryId = $this->request->get('participant_category_id');
        $isPaid = $this->request->get('is_paid');

        $query = Participant::with('participantCategory');

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

        if ($isPaid !== null && $isPaid !== '') {

            if ($isPaid == 1) {
                $query->whereHas('registrations', function ($q) {
                    $q->where('payment_step', 'paid');
                });
            }

            if ($isPaid == 0) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('registrations')
                      ->orWhereHas('registrations', function ($q2) {
                          $q2->where('payment_step', '!=', 'paid');
                      });
                });
            }
        }

        $participants = $query->orderBy('full_name')->get();

        $rows = [];
        $no = 1;

        foreach ($participants as $p) {

            $rows[] = [
                $no++,
                $p->full_name,
                (string) $p->nik,
                $p->email,
                (string) $p->mobile_phone,
                $p->institution,
                $p->participantCategory?->name,
                $p->registration_type,
                $p->is_paid ? 'Paid' : 'Unpaid',
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
            'Registration Type',
            'Payment Status',
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

                // Auto Filter
                $sheet->setAutoFilter('A1:I1');

                // Highlight Paid / Unpaid
                for ($row = 2; $row <= $highestRow; $row++) {

                    $status = $sheet->getCell("I{$row}")->getValue();

                    if ($status === 'Paid') {
                        $sheet->getStyle("I{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '008000']],
                        ]);
                    }

                    if ($status === 'Unpaid') {
                        $sheet->getStyle("I{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => 'C00000']],
                        ]);
                    }
                }
            }
        ];
    }
}