<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Report — {{ $employee['name'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; }

        .header { background: #2563EB; color: white; padding: 20px 24px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p  { font-size: 12px; margin-top: 4px; opacity: .85; }

        .meta { display: flex; gap: 32px; padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .meta-item label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; display: block; }
        .meta-item span  { font-size: 12px; font-weight: 600; color: #1e293b; }

        .section { padding: 16px 24px; }
        .section h2 { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 10px; padding-bottom: 4px; border-bottom: 2px solid #2563EB; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th    { background: #1e293b; color: white; text-align: left; padding: 7px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        td    { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .badge-approved  { background: #dcfce7; color: #166534; }
        .badge-submitted { background: #dbeafe; color: #1e40af; }
        .badge-rejected  { background: #fee2e2; color: #991b1b; }
        .badge-pending   { background: #fef9c3; color: #854d0e; }
        .badge-A { background: #dcfce7; color: #166534; }
        .badge-B { background: #dbeafe; color: #1e40af; }
        .badge-C { background: #fef9c3; color: #854d0e; }
        .badge-D { background: #ffedd5; color: #9a3412; }
        .badge-E { background: #fee2e2; color: #991b1b; }

        .score-big { font-size: 28px; font-weight: 800; color: #2563EB; }
        .summary-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 20px; margin-bottom: 16px; }

        .footer { margin-top: 32px; padding: 12px 24px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h1>Employee KPI Performance Report</h1>
    <p>Period: {{ $year }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}</p>
</div>

<div class="meta">
    <div class="meta-item">
        <label>Employee Name</label>
        <span>{{ $employee['name'] }}</span>
    </div>
    <div class="meta-item">
        <label>NIP</label>
        <span>{{ $employee['nip'] }}</span>
    </div>
    <div class="meta-item">
        <label>Position</label>
        <span>{{ $employee['position'] }}</span>
    </div>
    <div class="meta-item">
        <label>Department</label>
        <span>{{ $employee['department'] }}</span>
    </div>
    <div class="meta-item">
        <label>Annual Average</label>
        <span class="score-big">{{ number_format($annual_avg, 1) }}%</span>
    </div>
    <div class="meta-item">
        <label>Final Grade</label>
        <span class="badge badge-{{ $final_grade }}">{{ $final_grade }}</span>
    </div>
</div>

<div class="section">
    <h2>Monthly KPI Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th>Status</th>
                <th>Total Score</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthly_data as $month)
            <tr>
                <td>{{ $month['period'] }}</td>
                <td><span class="badge badge-{{ $month['status'] }}">{{ ucfirst($month['status']) }}</span></td>
                <td>{{ number_format($month['total_score'], 2) }}%</td>
                <td><span class="badge badge-{{ $month['grade'] }}">{{ $month['grade'] }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:#94a3b8;">No data available.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@foreach($monthly_data as $month)
@if(count($month['indicators']) > 0)
<div class="section">
    <h2>{{ $month['period'] }} — Indicator Detail</h2>
    <table>
        <thead>
            <tr>
                <th>Indicator</th>
                <th>Target</th>
                <th>Actual</th>
                <th>Achievement %</th>
                <th>Weighted Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach($month['indicators'] as $ind)
            <tr>
                <td>{{ $ind['name'] }}</td>
                <td>{{ number_format($ind['target'] ?? 0, 2) }}</td>
                <td>{{ number_format($ind['actual'] ?? 0, 2) }}</td>
                <td>{{ number_format($ind['achievement_percent'] ?? 0, 1) }}%</td>
                <td>{{ number_format($ind['weighted_score'] ?? 0, 4) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endforeach

<div class="footer">
    This document is system-generated. &copy; {{ date('Y') }} HR KPI Management System.
</div>

</body>
</html>
