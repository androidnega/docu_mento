<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export list of supervisors with phone, number of assigned projects, and number of students.
 * Columns: No., Name, Phone, Assigned Projects, Students.
 */
class SupervisorsListExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $supervisors
    ) {}

    public function title(): string
    {
        return 'Supervisors';
    }

    public function headings(): array
    {
        return [
            'No.',
            'Name',
            'Phone',
            'Assigned Projects',
            'Students',
        ];
    }

    /**
     * @param  object  $supervisor
     */
    public function map($supervisor): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $supervisor->name ?? '—',
            $supervisor->phone ?? '—',
            (int) ($supervisor->supervised_projects_count ?? 0),
            (int) ($supervisor->total_students_count ?? 0),
        ];
    }

    public function collection(): Collection
    {
        return $this->supervisors->values();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
