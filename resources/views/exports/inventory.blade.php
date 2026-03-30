<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Export</title>
    @php
        $searchValue = $search ?: 'None';
    @endphp
    <style>
        @@page {
            margin: 122px 24px 24px 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .header {
            position: fixed;
            top: -98px;
            left: 0;
            right: 0;
            height: 78px;
            border-bottom: 4px solid #fb923c;
            background: #ffffff;
            padding: 16px 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 52px;
            vertical-align: middle;
        }

        .logo {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 2px solid #ffffff;
            object-fit: cover;
            display: block;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
        }

        .title-cell {
            vertical-align: middle;
            padding-left: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .subtitle {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #c2410c;
            margin: 0;
        }

        .meta {
            width: 260px;
            margin: 0;
            font-size: 12px;
            color: #4b5563;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 0;
            text-align: right;
            vertical-align: top;
        }

        .meta-label {
            font-weight: 700;
            color: #111827;
        }

        .meta-value {
            padding-left: 4px;
            color: #4b5563;
        }

        .meta-wrapper {
            width: 280px;
            vertical-align: top;
        }

        .content {
            width: 100%;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.data th,
        table.data td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 11px;
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
    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img
                            src="{{ public_path('logo.JPG') }}"
                            alt="Biomed logo"
                            class="logo"
                            width="50"
                            height="50"
                            style="width: 50px; height: 50px;"
                        >
                    </td>
                    <td class="title-cell">
                        <p class="title">ADELA SERRA TY MEMORIAL MEDICAL CENTER</p>
                        <p class="subtitle">BIOMED PREVENTIVE MAINTENANCE</p>
                    </td>
                    <td class="meta-wrapper">
                        <table class="meta">
                            <tr>
                                <td><span class="meta-label">Range:</span><span class="meta-value">{{ $from->format('F Y') }} to {{ $to->format('F Y') }}</span></td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Generated:</span><span class="meta-value">{{ $generatedAt->format('F d, Y h:i A') }}</span></td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Search:</span><span class="meta-value">{{ $searchValue }}</span></td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Format:</span><span class="meta-value">{{ strtoupper($format ?? 'pdf') }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        <br>

        <div class="content">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 5%;">Item #</th>
                        <th style="width: 11%;">Location</th>
                        <th style="width: 18%;">Equipment Description</th>
                        <th style="width: 9%;">Brand</th>
                        <th style="width: 9%;">Model</th>
                        <th style="width: 10%;">Serial #</th>
                        <th style="width: 10%;">TAG #</th>
                        <th style="width: 10%;">PM Date Done</th>
                        <th style="width: 8%;">Calibration</th>
                        <th style="width: 10%;">Status</th>
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
    </div>
</body>
</html>