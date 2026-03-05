<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport extends StringValueBinder implements
    FromCollection,
    WithHeadings,
    WithCustomValueBinder,
    ShouldAutoSize,
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

        $query = User::with('role')
            ->whereHas('role', function ($q) {
                $q->whereNotIn('name', ['superadmin', 'participant']);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get();

        $rows = [];
        $no = 1;

        foreach ($users as $u) {

            $rows[] = [
                $no++,
                $u->name,
                $u->email,
                $u->username,
                (string) $u->nik,
                $u->role?->name,
                $u->can_multiple_role ? 'Yes' : 'No',
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Name',
            'Email',
            'Username',
            'NIK',
            'Role',
            'Multi Role',
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Freeze header
                $sheet->freezePane('A2');

                // Auto filter
                $sheet->setAutoFilter('A1:G1');

                // Highlight multi role
                for ($row = 2; $row <= $highestRow; $row++) {

                    $value = $sheet->getCell("G{$row}")->getValue();

                    if ($value === 'Yes') {
                        $sheet->getStyle("G{$row}")
                            ->getFont()
                            ->getColor()
                            ->setRGB('008000');
                    } else {
                        $sheet->getStyle("G{$row}")
                            ->getFont()
                            ->getColor()
                            ->setRGB('C00000');
                    }
                }
            }

        ];
    }
}