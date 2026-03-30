<!DOCTYPE html>
<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
    <meta charset="utf-8">
    <meta name="ProgId" content="Word.Document">
    <meta name="Generator" content="Microsoft Word 15">
    <title>Inventory Export</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>90</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page Section1 {
            size: 14in 8.5in;
            mso-page-orientation: landscape;
            margin: 0.35in;
        }

        div.Section1 {
            page: Section1;
            width: 13.3in;
        }

        body {
            font-family: Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 3px solid #fb923c;
            background: #ffffff;
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 48px;
            vertical-align: middle;
        }

        .logo {
            width: 38px;
            height: 38px;
            display: block;
        }

        .title-cell {
            vertical-align: middle;
            padding-left: 10px;
        }

        .title {
            font-size: 18pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 2px;
        }

        .subtitle {
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 1pt;
            text-transform: uppercase;
            color: #c2410c;
            margin: 0;
        }

        .meta {
            width: 260px;
            border-collapse: collapse;
            margin: 0;
            font-size: 8pt;
            color: #4b5563;
        }

        .meta strong {
            color: #111827;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta-right {
            text-align: right;
        }

        .meta-label {
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
        }

        .meta-value {
            padding-left: 4px;
            color: #4b5563;
        }

        .meta-wrapper {
            width: 280px;
            vertical-align: top;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.data th,
        table.data td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 7.5pt;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.data th {
            background: #f97316;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
        }

        table.data tr:nth-child(even) td {
            background: #fff7ed;
        }

        .status {
            font-weight: 700;
        }

        .status-functional { color: #166534; }
        .status-defective { color: #b91c1c; }
        .status-other { color: #a16207; }

        .calibrated { color: #166534; font-weight: 700; }
        .uncalibrated { color: #b91c1c; font-weight: 700; }
    </style>
</head>
<body>
    <div class="Section1">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ public_path('logo.JPG') }}" alt="Biomed logo" class="logo" width="40" height="40" style="width: 38px; height: 38px;">
                    </td>
                    <td class="title-cell">
                        <p class="title">ADELA SERRA TY MEMORIAL MEDICAL CENTER</p>
                        <p class="subtitle">BIOMED PREVENTIVE MAINTENANCE</p>
                    </td>
                    <td class="meta-wrapper">
                        <table class="meta">
                            <tr>
                                <td class="meta-right"><span class="meta-label">Range:</span><span class="meta-value">{{ $from->format('F Y') }} to {{ $to->format('F Y') }}</span></td>
                            </tr>
                            <tr>
                                <td class="meta-right"><span class="meta-label">Generated:</span><span class="meta-value">{{ $generatedAt->format('F d, Y h:i A') }}</span></td>
                            </tr>
                            <tr>
                                <td class="meta-right"><span class="meta-label">Search:</span><span class="meta-value">{{ request('search') ?: 'None' }}</span></td>
                            </tr>
                            <tr>
                                <td class="meta-right"><span class="meta-label">Format:</span><span class="meta-value">{{ strtoupper($format ?? 'word') }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">Item #</th>
                    <th style="width: 10%;">Location</th>
                    <th style="width: 20%;">Equipment Description</th>
                    <th style="width: 9%;">Brand</th>
                    <th style="width: 9%;">Model</th>
                    <th style="width: 11%;">Serial #</th>
                    <th style="width: 11%;">TAG #</th>
                    <th style="width: 10%;">PM Date Done</th>
                    <th style="width: 7%;">Calibration</th>
                    <th style="width: 9%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipments as $equipment)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $equipment->location }}</td>
                        <td>{{ $equipment->description }}</td>
                        <td>{{ $equipment->brand }}</td>
                        <td>{{ $equipment->model }}</td>
                        <td>{{ $equipment->serial_number }}</td>
                        <td>{{ $equipment->tag_number }}</td>
                        <td>{{ optional($equipment->pm_date_done ? \Carbon\Carbon::parse($equipment->pm_date_done) : null)?->format('F d, Y') }}</td>
                        <td class="{{ $equipment->calibration === 'Calibrated' ? 'calibrated' : 'uncalibrated' }}">
                            {{ $equipment->calibration ?: 'Uncalibrated' }}
                        </td>
                        <td class="status {{ $equipment->status === 'Functional' ? 'status-functional' : ($equipment->status === 'Defective' ? 'status-defective' : 'status-other') }}">
                            {{ $equipment->status }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; color: #6b7280;">No equipment found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>