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
    public const HEADERS = [
        'No. ID',
        'Nama',
        'Normal Hari Kerja',
        'Riil',
        'Absent',
        'Terlambat / Jam',
        'Tugas Dalam Kota',
        'WFH',
        'Izin',
        'CUTI',
        'Izin Sakit',
        'Jam Kompensasi',
        'Cuti Bersama',
        'Libur Perusahaan',
        'Libur Tanggal Merah',
        'Tugas Pelatihan',
        'Tugas Keluar Kota',
        'Keterangan',
        'Total Hari Hadir',
        '% Kehadiran',
        'Penilaian',
    ];

    public const NOTES = [
        'Libur Tanggal Merah = libur dari data hari libur nasional (tidak memotong cuti)',
        'Cuti Bersama = mengurangi saldo cuti tahunan karyawan',
        'Libur Perusahaan = kebijakan perusahaan, tidak mengurangi cuti tahunan karyawan',
        'Absent = total tidak hadir tanpa keterangan',
        'Terlambat = total terlambat dalam satu bulan (setiap keterlambatan dihitung minimal 1 jam, dibulatkan ke atas per jam)',
        'Hari Kerja = total hari periode dikurangi hari libur mingguan pada Pengaturan > Absensi',
        'Total Hari Hadir = jumlah hari dengan presensi tercatat pada periode',
        'Penilaian: >91% Sangat Baik, 71-90% Baik, 51-70% Kurang, <50% Sangat Kurang',
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
            'I' => 10,
            'J' => 10,
            'K' => 12,
            'L' => 15,
            'M' => 13,
            'N' => 18,
            'O' => 21,
            'P' => 16,
            'Q' => 17,
            'R' => 38,
            'S' => 16,
            'T' => 12,
            'U' => 16,
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
                $sheet->getStyle('A5:U6')->getAlignment()->setWrapText(true);

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
            $sheet->setCellValue("D{$excelRow}", "=C{$excelRow}-E{$excelRow}-I{$excelRow}-K{$excelRow}-M{$excelRow}-J{$excelRow}-O{$excelRow}-N{$excelRow}");
            $sheet->setCellValue("E{$excelRow}", $row['absent']);
            $sheet->setCellValue("F{$excelRow}", $row['late_hours']);
            $sheet->setCellValue("G{$excelRow}", $row['duty_city']);
            $sheet->setCellValue("H{$excelRow}", $row['wfh']);
            $sheet->setCellValue("I{$excelRow}", $row['permit']);
            $sheet->setCellValue("J{$excelRow}", $row['leave']);
            $sheet->setCellValue("K{$excelRow}", $row['sick_leave']);
            $sheet->setCellValue("L{$excelRow}", $row['compensation_hours']);
            $sheet->setCellValue("M{$excelRow}", $row['collective_leave']);
            $sheet->setCellValue("N{$excelRow}", $row['company_holiday']);
            $sheet->setCellValue("O{$excelRow}", $row['national_holiday']);
            $sheet->setCellValue("P{$excelRow}", $row['training']);
            $sheet->setCellValue("Q{$excelRow}", $row['duty_out_of_town']);
            $sheet->setCellValue("S{$excelRow}", $row['present_days']);
            $sheet->setCellValue("T{$excelRow}", $row['attendance_percentage'].'%');
            $sheet->setCellValue("U{$excelRow}", $row['attendance_grade']);
        }
    }

    private function reportTitle(): string
    {
        return self::titleText();
    }

    public static function titleText(): string
    {
        $company = trim((string) Setting::get('company_name', ''));

        return $company === ''
            ? 'ABSENSI KARYAWAN'
            : 'ABSENSI KARYAWAN '.mb_strtoupper($company);
    }

    private function periodLabel(): string
    {
        return self::periodText($this->report['start_date'], $this->report['end_date']);
    }

    public static function periodText(CarbonInterface $start, CarbonInterface $end): string
    {
        return sprintf(
            'PERIODE Tgl. %d %s s.d %d %s %d',
            $start->day,
            self::monthName($start->month),
            $end->day,
            self::monthName($end->month),
            $end->year
        );
    }

    private static function monthName(int $month): string
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
        $sheet->mergeCells('A2:U2');
        $sheet->mergeCells('A3:U3');

        foreach (self::HEADERS as $index => $header) {
            $column = $this->columnName($index + 1);
            $sheet->mergeCells("{$column}5:{$column}6");
        }

        $sheet->getStyle('A2:U2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 14, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A3:U3')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A5:U6')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF44546A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('I5:I6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('J5:J6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF70AD47');
        $sheet->getStyle('K5:K6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
    }

    private function styleData($sheet, int $lastDataRow): void
    {
        if ($lastDataRow < 7) {
            return;
        }

        $sheet->getStyle("A7:U{$lastDataRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle("B7:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("R7:R{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("U7:U{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C7:C{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle("D7:D{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF66');
        $sheet->getStyle("H7:H{$lastDataRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9DC3E6');

        for ($row = 7; $row <= $lastDataRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(16);
        }
    }

    private function writeNotes($sheet, int $notesRow): void
    {
        foreach (self::NOTES as $index => $note) {
            $row = $notesRow + $index;
            $sheet->mergeCells("Q{$row}:U{$row}");
            $sheet->setCellValue("Q{$row}", $note);
            $sheet->getStyle("Q{$row}:U{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }
    }
}
