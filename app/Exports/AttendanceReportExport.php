<?php

namespace App\Exports;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AttendanceReportExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    private const HEADERS = [
        'No. ID',
        'Nama',
        'Normal Hari Kerja',
        'Riil',
        'Absent',
        'Terlambat / Jam',
        'Tugas Dalam Kota',
        'WFH',
        'Tugas Pelatihan',
        'Tugas Keluar Kota',
        'Izin',
        'CUTI',
        'Izin Sakit',
        'Jam Kompensasi',
        'Cuti Bersama',
        'Libur Perusahaan',
        'Libur Tanggal Merah',
        'Keterangan',
    ];

    public function __construct(private array $report) {}

    public function array(): array
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 30,
            'C' => 12,
            'D' => 9,
            'E' => 16,
            'F' => 16,
            'G' => 16,
            'H' => 10,
            'I' => 16,
            'J' => 17,
            'K' => 10,
            'L' => 10,
            'M' => 12,
            'N' => 15,
            'O' => 13,
            'P' => 18,
            'Q' => 21,
            'R' => 38,
        ];
    }

    public function title(): string
    {
        return 'Absen';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = count($this->report['rows']) + 6;

                $this->writeReport($sheet, $lastDataRow);
                $this->styleHeader($sheet);
                $this->styleData($sheet, $lastDataRow);
                $this->writeNotes($sheet, $lastDataRow + 2);

                $sheet->freezePane('A7');
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(5)->setRowHeight(30);
                $sheet->getStyle('A5:R6')->getAlignment()->setWrapText(true);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->getPageMargins()->setTop(0.5)->setRight(0.3)->setBottom(0.5)->setLeft(0.3);
            },
        ];
    }

    private function writeReport($sheet, int $lastDataRow): void
    {
        $sheet->setCellValue('A2', $this->reportTitle());
        $sheet->setCellValue('A3', $this->periodLabel());

        foreach (self::HEADERS as $index => $header) {
            $column = $this->columnName($index + 1);
            $sheet->setCellValue("{$column}5", $header);
        }

        foreach ($this->report['rows'] as $index => $row) {
            $excelRow = $index + 7;

            $sheet->setCellValueExplicit("A{$excelRow}", (string) ($row['employee_id'] ?: $index + 1), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$excelRow}", mb_strtoupper($row['name']));
            $sheet->setCellValue("C{$excelRow}", $row['normal_work_days']);
            $sheet->setCellValue("D{$excelRow}", "=C{$excelRow}-E{$excelRow}-K{$excelRow}-M{$excelRow}-O{$excelRow}-L{$excelRow}-Q{$excelRow}-P{$excelRow}");
            $sheet->setCellValue("E{$excelRow}", $row['absent']);
            $sheet->setCellValue("F{$excelRow}", $row['late_hours']);
            $sheet->setCellValue("G{$excelRow}", $row['duty_city']);
            $sheet->setCellValue("H{$excelRow}", $row['wfh']);
            $sheet->setCellValue("I{$excelRow}", $row['training']);
            $sheet->setCellValue("J{$excelRow}", $row['duty_out_of_town']);
            $sheet->setCellValue("K{$excelRow}", $row['permit']);
            $sheet->setCellValue("L{$excelRow}", $row['leave']);
            $sheet->setCellValue("M{$excelRow}", $row['sick_leave']);
            $sheet->setCellValue("N{$excelRow}", $row['compensation_hours']);
            $sheet->setCellValue("O{$excelRow}", $row['collective_leave']);
            $sheet->setCellValue("P{$excelRow}", $row['company_holiday']);
            $sheet->setCellValue("Q{$excelRow}", $row['national_holiday']);
        }
    }

    private function reportTitle(): string
    {
        $company = trim((string) Setting::get('company_name', ''));

        return $company === ''
            ? 'ABSENSI KARYAWAN'
            : 'ABSENSI KARYAWAN '.mb_strtoupper($company);
    }

    private function periodLabel(): string
    {
        /** @var CarbonInterface $start */
        $start = $this->report['start_date'];
        /** @var CarbonInterface $end */
        $end = $this->report['end_date'];

        return sprintf(
            'PERIODE Tgl. %d %s s.d %d %s %d',
            $start->day,
            $this->monthName($start->month),
            $end->day,
            $this->monthName($end->month),
            $end->year
        );
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ][$month];
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function styleHeader($sheet): void
    {
        $sheet->mergeCells('A2:R2');
        $sheet->mergeCells('A3:R3');

        foreach (self::HEADERS as $index => $header) {
            $column = $this->columnName($index + 1);
            $sheet->mergeCells("{$column}5:{$column}6");
        }

        $sheet->getStyle('A2:R2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 14, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A3:R3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A5:R6')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF44546A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('K5:K6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('L5:L6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF70AD47');
        $sheet->getStyle('M5:M6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
    }

    private function styleData($sheet, int $lastDataRow): void
    {
        if ($lastDataRow < 7) {
            return;
        }

        $sheet->getStyle("A7:R{$lastDataRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("B7:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("R7:R{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C7:C{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle("D7:D{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF66');
        $sheet->getStyle("H7:H{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9DC3E6');

        for ($row = 7; $row <= $lastDataRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(16);
        }
    }

    private function writeNotes($sheet, int $notesRow): void
    {
        $notes = [
            'Libur Tanggal Merah = libur dari data hari libur nasional (tidak memotong cuti)',
            'Cuti Bersama = mengurangi saldo cuti tahunan karyawan',
            'Libur Perusahaan = kebijakan perusahaan, tidak mengurangi cuti tahunan karyawan',
            'Absent = total tidak hadir tanpa keterangan',
            'Terlambat = total terlambat dalam satu bulan (setiap keterlambatan dihitung minimal 1 jam, dibulatkan ke atas per jam)',
        ];

        foreach ($notes as $index => $note) {
            $row = $notesRow + $index;
            $sheet->mergeCells("Q{$row}:R{$row}");
            $sheet->setCellValue("Q{$row}", $note);
            $sheet->getStyle("Q{$row}:R{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }
    }
}
