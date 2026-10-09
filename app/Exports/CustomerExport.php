<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder, WithTitle
{
    public function query()
    {
        return Customer::query()
            ->select('id', 'name', 'phone', 'is_blacklisted', 'branch_id', 'created_at')
            ->with('branch:id,name')
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No. HP',
            'Status',
            'Cabang',
            'Tanggal Daftar',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            (string) $customer->phone,
            $customer->is_blacklisted ? 'Diblokir' : 'Aktif',
            optional($customer->branch)->name,
            $customer->created_at?->format('d M Y'),
        ];
    }
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'B') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1D4ED8'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9FAFB'],
                    ],
                ]);
            }

            $statusValue = $sheet->getCell("C{$row}")->getValue();
            $color = ($statusValue === 'Aktif') ? '16A34A' : 'DC2626';
            $bgColor = ($statusValue === 'Aktif') ? 'DCFCE7' : 'FEE2E2';
            $sheet->getStyle("E{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
    }

    public function title(): string
    {
        return 'Data Customer';
    }
}
