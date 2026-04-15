<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Request History Export</title>
    @php $searchValue = $search ?: 'None'; @endphp
    <style>
        @@page { margin: 122px 20px 24px 20px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; margin: 0; }
        .page { width: 100%; }

        .header {
            position: fixed;
            top: -98px; left: 0; right: 0;
            height: 78px;
            border-bottom: 4px solid #fb923c;
            background: #ffffff;
            padding: 16px 18px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo-cell { width: 52px; vertical-align: middle; }
        .logo { width: 38px; height: 38px; border-radius: 999px; object-fit: cover; display: block; }
        .title-cell { vertical-align: middle; padding-left: 10px; }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
        .subtitle { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #c2410c; margin: 0; }
        .meta { width: 260px; margin: 0; font-size: 11px; color: #4b5563; border-collapse: collapse; }
        .meta td { padding: 2px 0; text-align: right; vertical-align: top; }
        .meta-label { font-weight: 700; color: #111827; }
        .meta-value { padding-left: 4px; color: #4b5563; }
        .meta-wrapper { width: 280px; vertical-align: top; }

        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 9.5px;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.data th { background: #f97316; color: #ffffff; font-weight: 700; text-align: left; }
        table.data tr:nth-child(even) td { background: #fff7ed; }

        .minor  { color: #1D4ED8; font-weight: 700; }
        .major  { color: #C2410C; font-weight: 700; }
        .repaired      { color: #166534; font-weight: 700; }
        .unserviceable { color: #b91c1c; font-weight: 700; }
        .neutral { color: #374151; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('logo.JPG') }}" alt="Biomed logo" class="logo" width="50" height="50" style="width:50px;height:50px;">
                </td>
                <td class="title-cell">
                    <p class="title">ADELA SERRA TY MEMORIAL MEDICAL CENTER</p>
                    <p class="subtitle">BIOMED — JOB REQUEST HISTORY</p>
                </td>
                <td class="meta-wrapper">
                    <table class="meta">
                        <tr><td><span class="meta-label">Range: </span><span class="meta-value">{{ $from->format('F Y') }} to {{ $to->format('F Y') }}</span></td></tr>
                        <tr><td><span class="meta-label">Generated: </span><span class="meta-value">{{ $generatedAt->format('F d, Y h:i A') }}</span></td></tr>
                        <tr><td><span class="meta-label">Search: </span><span class="meta-value">{{ $searchValue }}</span></td></tr>
                        <tr><td><span class="meta-label">Format: </span><span class="meta-value">{{ strtoupper($format ?? 'pdf') }}</span></td></tr>
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
                    <th style="width:4%;">No.</th>
                    <th style="width:7%;">Control No.</th>
                    <th style="width:9%;">Location</th>
                    <th style="width:16%;">Equipment Description</th>
                    <th style="width:8%;">Brand</th>
                    <th style="width:8%;">Model</th>
                    <th style="width:9%;">Serial #</th>
                    <th style="width:8%;">TAG #</th>
                    <th style="width:9%;">Accepted By</th>
                    <th style="width:9%;">Technician</th>
                    <th style="width:6%;">Category</th>
                    <th style="width:7%;">Outcome</th>
                    <th style="width:10%;">Completed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $catClass = match($row['category']) { 'Minor' => 'minor', 'Major' => 'major', default => 'neutral' };
                        $outClass = match($row['outcome']) { 'Repaired' => 'repaired', 'Unserviceable' => 'unserviceable', default => 'neutral' };
                    @endphp
                    <tr>
                        <td>{{ $row['no'] }}</td>
                        <td>{{ $row['control_no'] }}</td>
                        <td>{{ $row['location'] }}</td>
                        <td>{{ $row['equipment_name'] }}</td>
                        <td>{{ $row['brand'] }}</td>
                        <td>{{ $row['model'] }}</td>
                        <td>{{ $row['serial_number'] }}</td>
                        <td>{{ $row['tag_number'] }}</td>
                        <td>{{ $row['accepted_by'] }}</td>
                        <td>{{ $row['technician'] }}</td>
                        <td class="{{ $catClass }}">{{ $row['category'] }}</td>
                        <td class="{{ $outClass }}">{{ $row['outcome'] }}</td>
                        <td>{{ $row['completed_at'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" style="text-align:center;color:#6b7280;">No records found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
