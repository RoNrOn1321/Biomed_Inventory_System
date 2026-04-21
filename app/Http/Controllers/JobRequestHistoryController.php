<?php

namespace App\Http\Controllers;

use App\Models\JobRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class JobRequestHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            in_array($request->user()?->account_type, ['Admin', 'Moderator', 'Biomed_Technician'], true),
            403
        );

        $search = $request->input('search');

        $query = JobRequest::query()
            ->with([
                'acceptedBy:id,name',
                'assignedTo:id,name',
                'linkedEquipment:id,location,description,brand,model,serial_number,tag_number',
            ])
            ->where('status', 'Done')
            ->orderByDesc('updated_at');

        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('equipment_name', 'like', $term)
                  ->orWhere('location', 'like', $term)
                  ->orWhere('requester_name', 'like', $term)
                  ->orWhere('control_no', 'like', $term);
            });
        }

        $history = $query->paginate(20)->withQueryString();

        $mapped = $history->through(fn (JobRequest $jr) => [
            'id'               => $jr->id,
            'equipment_id'     => $jr->linkedEquipment?->id ?? $jr->equipment_id,
            'control_no'       => $jr->control_no,
            'location'         => $jr->linkedEquipment?->location ?? $jr->location,
            'equipment_name'   => $jr->linkedEquipment?->description ?? $jr->equipment_name,
            'brand'            => $jr->linkedEquipment?->brand,
            'model'            => $jr->linkedEquipment?->model,
            'serial_number'    => $jr->linkedEquipment?->serial_number,
            'tag_number'       => $jr->linkedEquipment?->tag_number,
            'accepted_by'      => $jr->acceptedBy?->name,
            'assigned_to_name' => $jr->assignedTo?->name,
            'repair_category'  => $jr->repair_category,
            'repair_outcome'   => $jr->repair_outcome,
            'admin_approval'   => $jr->admin_approval,
            'completed_at'     => optional($jr->updated_at)->toIso8601String(),
            'requested_at'     => optional($jr->requested_at)->toIso8601String(),
        ]);

        return Inertia::render('JobHistory', [
            'history' => $mapped,
            'filters' => $request->only(['search']),
        ]);
    }

    public function export(Request $request)
    {
        abort_unless(
            in_array($request->user()?->account_type, ['Admin', 'Moderator', 'Biomed_Technician'], true),
            403
        );

        $validated = $request->validate([
            'format' => ['required', 'in:pdf,excel,word'],
            'from'   => ['required', 'date_format:Y-m'],
            'to'     => ['required', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $from = Carbon::createFromFormat('Y-m', $validated['from'])->startOfMonth();
        $to   = Carbon::createFromFormat('Y-m', $validated['to'])->endOfMonth();

        abort_if($from->gt($to), 422, 'The export date range is invalid.');

        $search = $validated['search'] ?? null;

        $rows = JobRequest::query()
            ->with([
                'acceptedBy:id,name',
                'assignedTo:id,name',
                'linkedEquipment:id,location,description,brand,model,serial_number,tag_number',
            ])
            ->where('status', 'Done')
            ->whereBetween('updated_at', [$from, $to])
            ->when($search, function ($q) use ($search) {
                $term = '%' . $search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('equipment_name', 'like', $term)
                          ->orWhere('location', 'like', $term)
                          ->orWhere('requester_name', 'like', $term)
                          ->orWhere('control_no', 'like', $term);
                });
            })
            ->orderByDesc('updated_at')
            ->get()
            ->values()
            ->map(fn (JobRequest $jr, int $index) => [
                'no'             => $index + 1,
                'control_no'     => $jr->control_no ?? '—',
                'location'       => $jr->linkedEquipment?->location ?? $jr->location ?? '—',
                'equipment_name' => $jr->linkedEquipment?->description ?? $jr->equipment_name,
                'brand'          => $jr->linkedEquipment?->brand ?? '—',
                'model'          => $jr->linkedEquipment?->model ?? '—',
                'serial_number'  => $jr->linkedEquipment?->serial_number ?? '—',
                'tag_number'     => $jr->linkedEquipment?->tag_number ?? '—',
                'accepted_by'    => $jr->acceptedBy?->name ?? '—',
                'technician'     => $jr->assignedTo?->name ?? '—',
                'category'       => $jr->repair_category ?? '—',
                'outcome'        => $jr->repair_outcome ?? '—',
                'completed_at'   => optional($jr->updated_at)->format('F d, Y'),
            ]);

        $filenameBase = sprintf('job-request-history-%s-to-%s', $from->format('Y-m'), $to->format('Y-m'));

        return match ($validated['format']) {
            'pdf'   => $this->exportPdf($rows, $from, $to, $search, $filenameBase),
            'excel' => $this->exportExcel($rows, $from, $to, $search, $filenameBase),
            'word'  => $this->exportWord($rows, $from, $to, $search, $filenameBase),
        };
    }

    // ─── PDF ─────────────────────────────────────────────────────────

    private function exportPdf($rows, Carbon $from, Carbon $to, ?string $search, string $filenameBase)
    {
        return Pdf::loadView('exports.job-history', [
            'rows'        => $rows,
            'from'        => $from,
            'to'          => $to,
            'generatedAt' => now(),
            'search'      => $search,
            'format'      => 'pdf',
        ])
        ->setPaper('legal', 'landscape')
        ->download("{$filenameBase}.pdf");
    }

    // ─── Excel ───────────────────────────────────────────────────────

    private function exportExcel($rows, Carbon $from, Carbon $to, ?string $search, string $filenameBase)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Job Request History');

        $sheet->getDefaultRowDimension()->setRowHeight(21);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A7');

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_FOLIO);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $sheet->getPageMargins()->setTop(0.3)->setRight(0.25)->setLeft(0.25)->setBottom(0.35)->setHeader(0.1)->setFooter(0.1);

        $totalCols = 13; // A–M
        $lastCol   = 'M';

        $sheet->mergeCells('B2:H2');
        $sheet->mergeCells('B3:H3');
        $sheet->mergeCells('J1:M1');
        $sheet->mergeCells('J2:M2');
        $sheet->mergeCells('J3:M3');
        $sheet->mergeCells('J4:M4');

        $sheet->setCellValue('B2', 'ADELA SERRA TY MEMORIAL MEDICAL CENTER');
        $sheet->setCellValue('B3', 'BIOMED — JOB REQUEST HISTORY');
        $sheet->setCellValue('J1', 'Range: ' . $from->format('F Y') . ' to ' . $to->format('F Y'));
        $sheet->setCellValue('J2', 'Generated: ' . now()->format('F d, Y h:i A'));
        $sheet->setCellValue('J3', 'Search: ' . ($search ?: 'None'));
        $sheet->setCellValue('J4', 'Format: XLSX');

        $sheet->getStyle("A1:{$lastCol}5")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFFFF']],
        ]);
        $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FB923C']]],
        ]);

        if (file_exists(public_path('logo.JPG'))) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath(public_path('logo.JPG'));
            $drawing->setName('Biomed Logo');
            $drawing->setCoordinates('A2');
            $drawing->setOffsetX(6)->setOffsetY(4)->setHeight(46);
            $drawing->setWorksheet($sheet);
        }

        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB('0F172A');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('EA580C');
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('J1:M4')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('111827');
        $sheet->getStyle('J1:M4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(12);
        $sheet->getRowDimension(6)->setRowHeight(10);

        $headers    = ['No.', 'Control No.', 'Location', 'Equipment Description', 'Brand', 'Model', 'Serial #', 'TAG #', 'Accepted By', 'Technician', 'Category', 'Outcome', 'Completed'];
        $headerRow  = 7;
        $columns    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($columns[$i] . $headerRow, $header);
        }

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'F97316']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'F59E0B']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        $dataStart = $headerRow + 1;
        $sheet->fromArray(
            $rows->map(fn ($r) => [
                $r['no'], $r['control_no'], $r['location'], $r['equipment_name'],
                $r['brand'], $r['model'], $r['serial_number'], $r['tag_number'],
                $r['accepted_by'], $r['technician'], $r['category'], $r['outcome'], $r['completed_at'],
            ])->all(),
            null,
            "A{$dataStart}"
        );

        $lastRow = max($dataStart, $headerRow + $rows->count());
        $sheet->getStyle("A{$dataStart}:{$lastCol}{$lastRow}")->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);
        $sheet->getStyle("A{$dataStart}:{$lastCol}{$lastRow}")->getFont()->setSize(10);

        for ($row = $dataStart; $row <= $lastRow; $row++) {
            if ($row % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF7ED');
            }
        }

        foreach (['A' => 6, 'B' => 13, 'C' => 16, 'D' => 27, 'E' => 13, 'F' => 14, 'G' => 16, 'H' => 15, 'I' => 18, 'J' => 18, 'K' => 10, 'L' => 14, 'M' => 16] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$lastRow}");

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, "{$filenameBase}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── Word ────────────────────────────────────────────────────────

    private function exportWord($rows, Carbon $from, Carbon $to, ?string $search, string $filenameBase)
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation'  => 'landscape',
            'pageSizeW'    => 18720,
            'pageSizeH'    => 12240,
            'marginTop'    => 720,
            'marginRight'  => 504,
            'marginBottom' => 504,
            'marginLeft'   => 504,
            'headerHeight' => 720,
        ]);

        $header     = $section->addHeader();
        $headerTable = $header->addTable([
            'width'             => 100 * 50,
            'unit'              => 'pct',
            'alignment'         => JcTable::START,
            'borderBottomSize'  => 12,
            'borderBottomColor' => 'FB923C',
            'cellMarginTop'     => 40,
            'cellMarginBottom'  => 40,
        ]);
        $headerTable->addRow();

        $logoCell = $headerTable->addCell(900, ['valign' => 'center']);
        if (file_exists(public_path('logo.JPG'))) {
            $logoCell->addImage(public_path('logo.JPG'), ['width' => 38, 'height' => 38]);
        }

        $titleCell = $headerTable->addCell(10800, ['valign' => 'center']);
        $titleCell->addText('ADELA SERRA TY MEMORIAL MEDICAL CENTER', ['bold' => true, 'size' => 16, 'color' => '0F172A']);
        $titleCell->addText('BIOMED — JOB REQUEST HISTORY', ['bold' => true, 'size' => 9, 'color' => 'C2410C', 'allCaps' => true]);

        $metaCell = $headerTable->addCell(4500, ['valign' => 'top']);
        $metaTextStyle  = ['size' => 8, 'color' => '4B5563'];
        $metaLabelStyle = ['bold' => true, 'size' => 8, 'color' => '111827'];
        foreach ([
            'Range'     => $from->format('F Y') . ' to ' . $to->format('F Y'),
            'Generated' => now()->format('F d, Y h:i A'),
            'Search'    => $search ?: 'None',
            'Format'    => 'DOCX',
        ] as $label => $value) {
            $run = $metaCell->addTextRun(['alignment' => Jc::END]);
            $run->addText($label . ': ', $metaLabelStyle);
            $run->addText($value, $metaTextStyle);
        }

        $section->addTextBreak(1);

        $tableStyle = 'JobHistoryTable';
        $phpWord->addTableStyle($tableStyle, [
            'width'           => 100 * 50,
            'unit'            => 'pct',
            'borderSize'      => 6,
            'borderColor'     => 'D1D5DB',
            'cellMarginLeft'  => 45,
            'cellMarginRight' => 45,
            'cellMarginTop'   => 28,
            'cellMarginBottom'=> 28,
            'alignment'       => JcTable::START,
        ]);

        $table = $section->addTable($tableStyle);
        $table->addRow();

        $hStyle = ['bgColor' => 'F97316', 'valign' => 'center'];
        $hFont  = ['bold' => true, 'color' => 'FFFFFF', 'size' => 8];
        $hPara  = ['alignment' => Jc::START, 'spaceAfter' => 0];

        foreach ([
            ['No.',                  500],
            ['Control No.',         1000],
            ['Location',            1700],
            ['Equipment Description',3500],
            ['Brand',               1200],
            ['Model',               1200],
            ['Serial #',            1500],
            ['TAG #',               1400],
            ['Accepted By',         1700],
            ['Technician',          1700],
            ['Category',             900],
            ['Outcome',             1100],
            ['Completed',           1500],
        ] as [$label, $width]) {
            $table->addCell($width, $hStyle)->addText($label, $hFont, $hPara);
        }

        foreach ($rows->all() as $i => $r) {
            $rowStyle = ['bgColor' => $i % 2 === 1 ? 'FFF7ED' : 'FFFFFF', 'valign' => 'top'];
            $f        = ['size' => 7.5];
            $table->addRow();
            $table->addCell(500,  $rowStyle)->addText((string) $r['no'],             $f, ['alignment' => Jc::END,   'spaceAfter' => 0]);
            $table->addCell(1000, $rowStyle)->addText((string) $r['control_no'],     $f, ['spaceAfter' => 0]);
            $table->addCell(1700, $rowStyle)->addText((string) $r['location'],       $f, ['spaceAfter' => 0]);
            $table->addCell(3500, $rowStyle)->addText((string) $r['equipment_name'], $f, ['spaceAfter' => 0]);
            $table->addCell(1200, $rowStyle)->addText((string) $r['brand'],          $f, ['spaceAfter' => 0]);
            $table->addCell(1200, $rowStyle)->addText((string) $r['model'],          $f, ['spaceAfter' => 0]);
            $table->addCell(1500, $rowStyle)->addText((string) $r['serial_number'],  $f, ['spaceAfter' => 0]);
            $table->addCell(1400, $rowStyle)->addText((string) $r['tag_number'],     $f, ['spaceAfter' => 0]);
            $table->addCell(1700, $rowStyle)->addText((string) $r['accepted_by'],    $f, ['spaceAfter' => 0]);
            $table->addCell(1700, $rowStyle)->addText((string) $r['technician'],     $f, ['spaceAfter' => 0]);

            $catColor = match ($r['category']) {
                'Minor' => '1D4ED8',
                'Major' => 'C2410C',
                default => '111827',
            };
            $table->addCell(900, $rowStyle)->addText((string) $r['category'], array_merge($f, ['bold' => true, 'color' => $catColor]), ['spaceAfter' => 0]);

            $outColor = match ($r['outcome']) {
                'Repaired'      => '166534',
                'Unserviceable' => 'B91C1C',
                default         => '111827',
            };
            $table->addCell(1100, $rowStyle)->addText((string) $r['outcome'], array_merge($f, ['bold' => true, 'color' => $outColor]), ['spaceAfter' => 0]);
            $table->addCell(1500, $rowStyle)->addText((string) $r['completed_at'], $f, ['spaceAfter' => 0]);
        }

        if ($rows->isEmpty()) {
            $table->addRow();
            $table->addCell(17900, ['gridSpan' => 13, 'valign' => 'center'])
                  ->addText('No records found for the selected period.', ['size' => 8, 'color' => '6B7280'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath, "{$filenameBase}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}

