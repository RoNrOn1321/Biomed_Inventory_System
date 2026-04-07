<?php

namespace App\Services;

use App\Models\Equipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EquipmentService
{
    private const EXPORT_FORMATS = ['pdf', 'excel', 'word'];

    public function getExportFormats(): array
    {
        return self::EXPORT_FORMATS;
    }

    public function paginatedList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Equipment
    {
        $data['calibration'] = 'Uncalibrated';

        return Equipment::create($data);
    }

    public function update(Equipment $equipment, array $data): void
    {
        $equipment->update($data);
    }

    public function delete(Equipment $equipment): void
    {
        $equipment->delete();
    }

    public function export(string $format, Carbon $from, Carbon $to, ?string $search, ?string $status = null)
    {
        $equipment = $this->buildExportQuery($from, $to, $search, $status)->get();
        $filenameBase = sprintf('inventory-%s-to-%s', $from->format('Y-m'), $to->format('Y-m'));

        return match ($format) {
            'pdf' => $this->downloadPdf($equipment, $from, $to, $search, $filenameBase),
            'excel' => $this->downloadExcel($equipment, $from, $to, $search, $filenameBase),
            'word' => $this->downloadWord($equipment, $from, $to, $search, $filenameBase),
        };
    }

    // ─── Query Builders ──────────────────────────────────────────────

    private function buildQuery(array $filters)
    {
        $query = Equipment::query()->latest('pm_date_done');

        if (!empty($filters['year'])) {
            $query->whereYear('pm_date_done', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('pm_date_done', $filters['month']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                    ->orWhere('location', 'like', $searchTerm)
                    ->orWhere('brand', 'like', $searchTerm)
                    ->orWhere('model', 'like', $searchTerm)
                    ->orWhere('serial_number', 'like', $searchTerm)
                    ->orWhere('tag_number', 'like', $searchTerm);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    private function buildExportQuery(Carbon $from, Carbon $to, ?string $search, ?string $status = null)
    {
        $query = Equipment::query()
            ->whereBetween('pm_date_done', [$from->toDateString(), $to->toDateString()])
            ->orderBy('pm_date_done')
            ->orderBy('description');

        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                    ->orWhere('location', 'like', $searchTerm)
                    ->orWhere('brand', 'like', $searchTerm)
                    ->orWhere('model', 'like', $searchTerm)
                    ->orWhere('serial_number', 'like', $searchTerm)
                    ->orWhere('tag_number', 'like', $searchTerm);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    // ─── Export: PDF ─────────────────────────────────────────────────

    private function downloadPdf(Collection $equipment, Carbon $from, Carbon $to, ?string $search, string $filenameBase)
    {
        $viewData = [
            'equipments' => $equipment,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
            'search' => $search,
            'format' => 'pdf',
        ];

        return Pdf::loadView('exports.inventory', $viewData)
            ->setPaper('legal', 'landscape')
            ->download("{$filenameBase}.pdf");
    }

    // ─── Export: Excel ───────────────────────────────────────────────

    private function downloadExcel(Collection $equipment, Carbon $from, Carbon $to, ?string $search, string $filenameBase): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory');

        $sheet->getDefaultRowDimension()->setRowHeight(21);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A7');

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_FOLIO);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.3)
            ->setRight(0.25)
            ->setLeft(0.25)
            ->setBottom(0.35)
            ->setHeader(0.1)
            ->setFooter(0.1);

        $sheet->mergeCells('B2:G2');
        $sheet->mergeCells('B3:G3');
        $sheet->mergeCells('I1:J1');
        $sheet->mergeCells('I2:J2');
        $sheet->mergeCells('I3:J3');
        $sheet->mergeCells('I4:J4');

        $generatedAt = now();

        $sheet->setCellValue('B2', 'ADELA SERRA TY MEMORIAL MEDICAL CENTER');
        $sheet->setCellValue('B3', 'BIOMED PREVENTIVE MAINTENANCE');
        $sheet->setCellValue('I1', 'Range: ' . $from->format('F Y') . ' to ' . $to->format('F Y'));
        $sheet->setCellValue('I2', 'Generated: ' . $generatedAt->format('F d, Y h:i A'));
        $sheet->setCellValue('I3', 'Search: ' . ($search ?: 'None'));
        $sheet->setCellValue('I4', 'Format: XLSX');

        $sheet->getStyle('A1:J5')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFFFF']],
        ]);
        $sheet->getStyle('A5:J5')->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FB923C']]],
        ]);

        if (file_exists(public_path('logo.JPG'))) {
            $drawing = new Drawing();
            $drawing->setPath(public_path('logo.JPG'));
            $drawing->setName('Biomed Logo');
            $drawing->setCoordinates('A2');
            $drawing->setOffsetX(6);
            $drawing->setOffsetY(4);
            $drawing->setHeight(46);
            $drawing->setWorksheet($sheet);
        }

        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(22)->getColor()->setARGB('0F172A');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('EA580C');
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I1:J4')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('111827');
        $sheet->getStyle('I1:J4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I1:J4')->getAlignment()->setWrapText(false);

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(12);
        $sheet->getRowDimension(6)->setRowHeight(10);

        $headers = ['Item #', 'Location', 'Equipment Description', 'Brand', 'Model', 'Serial #', 'TAG #', 'PM Date Done', 'Calibration', 'Status'];
        $headerRow = 7;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . $headerRow, $header);
        }

        $sheet->getStyle('A7:J7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'F97316']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'F59E0B']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        $sheet->fromArray(
            $equipment->values()->map(fn (Equipment $item, int $index) => [
                $index + 1,
                $item->location,
                $item->description,
                $item->brand,
                $item->model,
                $item->serial_number,
                $item->tag_number,
                $item->pm_date_done ? Carbon::parse($item->pm_date_done)->format('F d, Y') : '',
                $item->calibration ?: 'Uncalibrated',
                $item->status,
            ])->all(),
            null,
            'A8'
        );

        $lastRow = max(8, $headerRow + $equipment->count());
        $sheet->getStyle("A8:J{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);

        $sheet->getStyle("A8:J{$lastRow}")->getFont()->setSize(10);
        $sheet->getStyle("A8:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        for ($row = 8; $row <= $lastRow; $row++) {
            if ($row % 2 === 1) {
                $sheet->getStyle("A{$row}:J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF7ED');
            }
        }

        foreach (range(8, $lastRow) as $row) {
            $calibrationValue = (string) $sheet->getCell("I{$row}")->getValue();
            $statusValue = (string) $sheet->getCell("J{$row}")->getValue();

            if ($calibrationValue === 'Calibrated') {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setARGB('166534');
                $sheet->getStyle("I{$row}")->getFont()->setBold(true);
            } elseif ($calibrationValue !== '') {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setARGB('B91C1C');
                $sheet->getStyle("I{$row}")->getFont()->setBold(true);
            }

            if ($statusValue === 'Functional') {
                $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('166534');
            } elseif ($statusValue === 'Defective' || $statusValue === 'Unserviceable') {
                $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('B91C1C');
            } elseif ($statusValue !== '') {
                $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('A16207');
            }

            if ($statusValue !== '') {
                $sheet->getStyle("J{$row}")->getFont()->setBold(true);
            }
        }

        foreach (['A' => 8, 'B' => 16, 'C' => 27, 'D' => 13, 'E' => 14, 'F' => 16, 'G' => 15, 'H' => 16, 'I' => 14, 'J' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getStyle('A1:J' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getPageSetup()->setPrintArea("A1:J{$lastRow}");

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "{$filenameBase}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── Export: Word ────────────────────────────────────────────────

    private function downloadWord(Collection $equipment, Carbon $from, Carbon $to, ?string $search, string $filenameBase): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 18720,
            'pageSizeH' => 12240,
            'marginTop' => 720,
            'marginRight' => 504,
            'marginBottom' => 504,
            'marginLeft' => 504,
            'headerHeight' => 720,
        ]);

        $header = $section->addHeader();
        $headerTable = $header->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => JcTable::START,
            'borderBottomSize' => 12,
            'borderBottomColor' => 'FB923C',
            'cellMarginTop' => 40,
            'cellMarginBottom' => 40,
        ]);

        $headerTable->addRow();
        $logoCell = $headerTable->addCell(900, ['valign' => 'center']);
        if (file_exists(public_path('logo.JPG'))) {
            $logoCell->addImage(public_path('logo.JPG'), [
                'width' => 38,
                'height' => 38,
            ]);
        }

        $titleCell = $headerTable->addCell(10800, ['valign' => 'center']);
        $titleCell->addText('ADELA SERRA TY MEMORIAL MEDICAL CENTER', ['bold' => true, 'size' => 18, 'color' => '0F172A']);
        $titleCell->addText('BIOMED PREVENTIVE MAINTENANCE', ['bold' => true, 'size' => 9, 'color' => 'C2410C', 'allCaps' => true]);

        $metaCell = $headerTable->addCell(4500, ['valign' => 'top']);
        $metaTextStyle = ['size' => 8, 'color' => '4B5563'];
        $metaLabelStyle = ['bold' => true, 'size' => 8, 'color' => '111827'];
        foreach ([
            'Range' => $from->format('F Y') . ' to ' . $to->format('F Y'),
            'Generated' => now()->format('F d, Y h:i A'),
            'Search' => $search ?: 'None',
            'Format' => 'DOCX',
        ] as $label => $value) {
            $textRun = $metaCell->addTextRun(['alignment' => Jc::END]);
            $textRun->addText($label . ': ', $metaLabelStyle);
            $textRun->addText($value, $metaTextStyle);
        }

        $section->addTextBreak(1);

        $tableStyleName = 'InventoryTable';
        $phpWord->addTableStyle($tableStyleName, [
            'width' => 100 * 50,
            'unit' => 'pct',
            'borderSize' => 6,
            'borderColor' => 'D1D5DB',
            'cellMarginLeft' => 45,
            'cellMarginRight' => 45,
            'cellMarginTop' => 28,
            'cellMarginBottom' => 28,
            'alignment' => JcTable::START,
        ]);

        $table = $section->addTable($tableStyleName);
        $table->addRow();

        $headerCellStyle = ['bgColor' => 'F97316', 'valign' => 'center'];
        $headerFontStyle = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9];
        $headerParagraphStyle = ['alignment' => Jc::START, 'spaceAfter' => 0];
        foreach ([
            ['Item #', 700],
            ['Location', 1900],
            ['Equipment Description', 4100],
            ['Brand', 1550],
            ['Model', 1550],
            ['Serial #', 1850],
            ['TAG #', 1700],
            ['PM Date Done', 1800],
            ['Calibration', 1350],
            ['Status', 1500],
        ] as [$label, $width]) {
            $table->addCell($width, $headerCellStyle)->addText($label, $headerFontStyle, $headerParagraphStyle);
        }

        foreach ($equipment->values() as $index => $item) {
            $rowCellStyle = [
                'bgColor' => $index % 2 === 1 ? 'FFF7ED' : 'FFFFFF',
                'valign' => 'top',
            ];

            $table->addRow();
            $table->addCell(700, $rowCellStyle)->addText((string) ($index + 1), ['size' => 7.5], ['alignment' => Jc::END, 'spaceAfter' => 0]);
            $table->addCell(1900, $rowCellStyle)->addText((string) $item->location, ['size' => 7.5]);
            $table->addCell(4100, $rowCellStyle)->addText((string) $item->description, ['size' => 7.5]);
            $table->addCell(1550, $rowCellStyle)->addText((string) $item->brand, ['size' => 7.5]);
            $table->addCell(1550, $rowCellStyle)->addText((string) $item->model, ['size' => 7.5]);
            $table->addCell(1850, $rowCellStyle)->addText((string) $item->serial_number, ['size' => 7.5]);
            $table->addCell(1700, $rowCellStyle)->addText((string) $item->tag_number, ['size' => 7.5]);
            $table->addCell(1800, $rowCellStyle)->addText($item->pm_date_done ? Carbon::parse($item->pm_date_done)->format('F d, Y') : '', ['size' => 7.5]);

            $calibration = $item->calibration ?: 'Uncalibrated';
            $table->addCell(1350, $rowCellStyle)->addText($calibration, [
                'size' => 7.5,
                'bold' => true,
                'color' => $calibration === 'Calibrated' ? '166534' : 'B91C1C',
            ]);

            $statusColor = match ($item->status) {
                'Functional' => '166534',
                'Defective', 'Unserviceable' => 'B91C1C',
                default => 'A16207',
            };
            $table->addCell(1500, $rowCellStyle)->addText((string) $item->status, ['size' => 7.5, 'bold' => true, 'color' => $statusColor]);
        }

        if ($equipment->isEmpty()) {
            $table->addRow();
            $table->addCell(17400, ['gridSpan' => 10, 'valign' => 'center'])->addText(
                'No equipment found for the selected period.',
                ['size' => 8, 'color' => '6B7280'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, "{$filenameBase}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
