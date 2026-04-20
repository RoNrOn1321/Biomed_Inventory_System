<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Request Form</title>
    <style>
        @@page { margin: 12mm 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size:12px }
        .header-box { width:100%; border:1px solid #000; padding:0px; }
        /* Use table layout below for reliable PDF rendering (some PDF engines lack flex support) */
        .header-top { padding:8px 0 }
        .logo { width:80px; height:80px; object-fit:cover; margin-right:8px; display:block }
        .title-center { text-align:center }
        .title-main { font-size:14px; font-weight:700; margin:0 }
        .title-sub { font-size:12px; margin:0; }
        .section-title { color:#ef4444; font-weight:700; text-align:center; margin:8px 0; font-size:12px }
        table.form { width:100%; border-collapse:collapse; margin-bottom:8px }
        table.form td, table.form th { border:1px solid #000; padding:6px; vertical-align:top }
        .no-border td { border:none }
        .thin { border:1px solid #000 }
        .checkbox { width:20px; height:20px; border:1px solid #000; display:inline-block; vertical-align:middle; margin-right:8px; font-size:14px; line-height:18px; text-align:center; font-weight:700 }
        .muted { color:#6b7280; font-weight:700; font-size:10px }
        .small { font-size:11px }
        .signature { height:0; border-bottom:1px solid #000; width:240px; margin:6px auto }
        .remarks { min-height:0 }
    </style>
</head>
<body>
    
    <div class="header-box">
        <div class="header-top">
            <table style="width:100%; border-collapse:collapse; border:0">
                <tr>
                    <td style="width:88px; vertical-align:middle; text-align:left; border:0; padding:8px 12px 8px 8px">
                        @if(file_exists(public_path('logo.JPG')))
                            <img src="{{ public_path('logo.JPG') }}" class="logo" alt="logo" />
                        @endif
                    </td>
                    <td style="vertical-align:middle; text-align:center; border:0; padding:8px 8px">
                        <p class="title-sub">Department of Health</p>
                        <p class="title-main">ADELA SERRA TY MEMORIAL MEDICAL CENTER</p>
                        <p class="title-sub">JOB REQUEST FORM</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p class="section-title">BASIC INFORMATION</p>

    <table class="form">
        <tr>
            <td style="width:40%">
                <div class="muted">Requested By</div>
                <div class="small">{{ $job->requester_name ?? ' ' }}</div>
            </td>
            <td style="width:20%">
                <div class="muted">Date</div>
                <div class="small">{{ $job->requested_at ? \Carbon\Carbon::parse($job->requested_at)->format('F d, Y') : ' ' }}</div>
            </td>
            <td style="width:40%">
                <div class="muted">Control #</div>
                <div class="small">{{ $job->control_no ?? ' ' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="muted">Printed Name and Signature / Requesting Department</div>
                <div class="small">{{ $job->department ?? ' ' }}</div>
            </td>
            <td>
                <div class="muted">Location</div>
                <div class="small">{{ $job->location ?? ($job->linkedEquipment?->location ?? ' ') }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="muted">Description of Equipment and Accessories</div>
                <table style="width:100%; border-collapse:collapse; margin-top:6px">
                    <tr>
                        <th style="border:1px solid #000; padding:6px">Name</th>
                        <th style="border:1px solid #000; padding:6px">Brand</th>
                        <th style="border:1px solid #000; padding:6px">Model</th>
                        <th style="border:1px solid #000; padding:6px">Serial Number</th>
                        <th style="border:1px solid #000; padding:6px">End User</th>
                    </tr>
                    @php
                        $items = $job->descEquAccessories ?? collect();
                        if (is_null($items) || (is_countable($items) && count($items) === 0)) {
                            // Fallback to linked equipment or job fields when no detail rows exist
                            $fallbackName = $job->equipment_name ?? ($job->linkedEquipment?->description ?? '');
                            $items = collect([
                                (object)[
                                    'name' => $fallbackName,
                                    'brand' => $job->linkedEquipment?->brand ?? '',
                                    'model' => $job->linkedEquipment?->model ?? '',
                                    'serial_number' => $job->linkedEquipment?->serial_number ?? '',
                                    'end_user' => $job->end_user ?? '',
                                ]
                            ]);
                        }
                    @endphp

                    @foreach($items as $item)
                        <tr>
                            <td style="height:28px">{{ $item->name ?? ' ' }}</td>
                            <td>{{ $item->brand ?? ' ' }}</td>
                            <td>{{ $item->model ?? ' ' }}</td>
                            <td>{{ $item->serial_number ?? ' ' }}</td>
                            <td>{{ $item->end_user ?? ' ' }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <style>
        /* Force black borders and consistent spacing inside export section */
        .export-section table { border-collapse: collapse; }
        .export-section table, .export-section td, .export-section th { border: 0; }
        .export-section td, .export-section th { border:1px solid #000; }
        .export-section td { border-style: solid; }
        .export-section .remarks { border:1px solid #000 !important; padding:8px; background:#fff }
        .export-section .muted { color:#374151; font-weight:700 }
        .export-section .checkbox { display:inline-block; width:20px; height:20px; border:1px solid #000; vertical-align:middle; margin-right:8px; font-size:14px; line-height:18px; text-align:center; font-weight:700 }
    </style>

    <div class="export-section" style="border:none;">
        <div style=" padding:6px; border-bottom:none; text-align:center;">
            <strong style="color:#ef4444; letter-spacing:0.06em;">REQUEST DETAIL</strong>
        </div>

        <table style="width:100%; border-collapse:collapse; border:none; table-layout:fixed;">
            <tr>
                <td colspan="3" style="background:#f3f4f6; padding:10px; border-top:1px solid #000; border-bottom:1px solid #000; text-align:center; font-size:11px; color:#374151; font-weight:700;">
                    Provide a detailed indication of the problem in the space below, including symptoms, settings, error codes, circumstances, and services desired.
                </td>
            </tr>

            <tr>
                @php
                    $rt = strtolower($job->requestDetail?->request_type ?? $job->request_type ?? '');
                    $repairFlag = str_contains($rt, 'repair');
                    $preInspectionFlag = str_contains($rt, 'pre') || str_contains($rt, 'inspection');
                    $installFlag = str_contains($rt, 'install');
                    $calibrateFlag = str_contains($rt, 'calibrat');
                    $performanceFlag = str_contains($rt, 'performance');
                    $newEquipmentFlag = str_contains($rt, 'new');
                    $deliveryFlag = str_contains($rt, 'delivery') || str_contains($rt, 'accept');
                    $othersFlag = str_contains($rt, 'other');
                    $repairCategory = strtolower($job->repair_category ?? $job->repair?->repair_category ?? $job->biomedicalServiceDoc?->repair_category ?? '');
                    $minorFlag = $repairCategory === 'minor';
                    $majorFlag = $repairCategory === 'major';
                    $assignedTech = $job->assignedTo?->name ?? $job->acceptedBy?->name ?? '';
                @endphp

                <!-- Left column -->
                <td style="width:33.3333%; vertical-align:top; padding:12px; border-right:1px solid #000;">
                    <div style="margin-bottom:6px">
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $repairFlag ? '✓' : '' }}</span> Repair</div>
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $preInspectionFlag ? '✓' : '' }}</span> Pre-Inspection</div>
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $installFlag ? '✓' : '' }}</span> Install Equipment</div>
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $calibrateFlag ? '✓' : '' }}</span> Calibrate Equipment</div>
                    </div>
                </td>

                <!-- Middle column -->
                <td style="width:33.3333%; vertical-align:top; padding:12px; border-right:1px solid #000;">
                    <div style="margin-bottom:8px">
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $performanceFlag ? '✓' : '' }}</span> Performance Test</div>
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $newEquipmentFlag ? '✓' : '' }}</span> New Equipment</div>
                        <div style="margin-bottom:8px"><span class="checkbox">{{ $deliveryFlag ? '✓' : '' }}</span> Delivery Inspection &amp; Acceptance</div>
                        <div style="margin-top:6px"><span class="checkbox">{{ $othersFlag ? '✓' : '' }}</span> Others ________________________________</div>
                    </div>
                </td>

                <!-- Right column -->
                <td style="width:33.3333%; vertical-align:top; padding:12px;">
                    <div style="margin-bottom:6px">
                        <div style="margin-bottom:6px"><span class="checkbox">{{ $minorFlag ? '✓' : '' }}</span> MINOR REPAIR</div>
                        <div style="margin-bottom:6px"><span class="checkbox">{{ $majorFlag ? '✓' : '' }}</span> MAJOR REPAIR</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="padding:5px; border-top:1px solid #000">
                    <div class="muted">NATURE OF WORK REQUESTED / COMPLAINTS:</div>
                    @php
                        $requestDetailText = $job->request_complaints
                            ?? $job->issue_summary
                            ?? ($job->requestDetail?->request_type ?? null)
                            ?? ($job->linkedEquipment?->description ?? null);
                    @endphp
                    <div class="small remarks">{{ $requestDetailText ? $requestDetailText : ' ' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding:5px; border-top:1px solid #000">
                    <div class="muted">JOB REPORT:</div>
                    @php
                        $jobReportText = $job->job_report
                            ?? $job->repair?->repair_notes
                            ?? $job->issue_summary
                            ?? null;
                    @endphp
                    <div class="small remarks">{{ $jobReportText ? $jobReportText : ' ' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <p class="section-title">BIOMEDICAL SERVICE DOCUMENTATION</p>

    <table class="form">
        <tr>
            <td style="width:33%">Received by:</td>
            <td style="width:33%">Date Received:</td>
            <td style="width:34%">Technician Date Received:</td>
        </tr>
        @php
            $bs = $job->biomedicalServiceDoc;
            $receiveBy = $bs->receive_by ?? $job->acceptedBy?->name ?? ' ';
            $dateReceive = optional($bs->date_receive)->format('F d, Y') ?? ($job->accepted_at ? \Carbon\Carbon::parse($job->accepted_at)->format('F d, Y') : ' ');
            $techDateReceived = optional($bs->technician_date_received)->format('F d, Y') ?? ' ';
        @endphp
        <tr>
            <td style="height:36px">{{ $receiveBy }}</td>
            <td>{{ $dateReceive }}</td>
            <td>{{ $techDateReceived }}</td>
        </tr>
        <tr>
            <td colspan="3">
                <table style="width:100%; border-collapse:collapse">
                    <tr>
                        <th style="border:1px solid #000; padding:6px">Performed by (Name & Signature / Position)</th>
                        <th style="border:1px solid #000; padding:6px">Date Performed</th>
                        <th style="border:1px solid #000; padding:6px">Estimated No. of Days</th>
                        <th style="border:1px solid #000; padding:6px">Work Completion</th>
                        <th style="border:1px solid #000; padding:6px">Date Returned</th>
                    </tr>
                    @php
                        $performedBy = $bs->performed_by ?? $job->acceptedBy?->name ?? ' ';
                        $datePerformed = optional($bs->date_performed)->format('F d, Y') ?? ($job->accepted_at ? \Carbon\Carbon::parse($job->accepted_at)->format('F d, Y') : ' ');
                        $estimatedDays = $bs->estimated_no_days ?? ' ';
                        $dateStarted = optional($bs->date_started)->format('F d, Y') ?? '';
                        $dateFinished = optional($bs->date_finished)->format('F d, Y') ?? '';
                        $workCompletion = trim($dateStarted . ($dateStarted || $dateFinished ? ' - ' : '') . $dateFinished);
                        $dateReturned = optional($bs->date_returned)->format('F d, Y') ?? ' ';
                    @endphp
                    <tr>
                        <td style="height:36px">{{ $performedBy }}</td>
                        <td>{{ $datePerformed }}</td>
                        <td>{{ $estimatedDays }}</td>
                        <td>{{ $workCompletion ?: ' ' }}</td>
                        <td>{{ $dateReturned }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="muted">Check and Verified By:</div>
                <div style="text-align:center; margin-top:2px">
                    @php
                        // Prefer admin reviewer, then biomedicalServiceDoc receiver, then accepter, fallback to hardcoded name
                        $adminName = $job->adminReviewedBy?->name
                            ?? $job->biomedicalServiceDoc?->receive_by
                            ?? $job->acceptedBy?->name
                            ?? 'JUNEL R. CABUGA';
                        $adminTitle = 'BIOMED HEAD UNIT';
                    @endphp
                    <div style="text-align:center; font-weight:700; margin-bottom:4px">{{ strtoupper($adminName) }}</div>
                    <div class="signature" style="width:240px; margin:0 auto; border-bottom:1px solid #000"></div>
                    <div style="text-align:center; font-weight:700; margin-top:6px">{{ strtoupper($adminTitle) }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="muted">Remarks:</div>
                <div class="small remarks">{{ $bs->remarks ?? ' ' }}</div>

            </td>
        </tr>
    </table>

 

    <div style="margin-top:12px; text-align:center; font-size:11px; color:#ef4444">For Emergency Requests - call us with this Fanvil No. 2012</div>
</body>
</html>
