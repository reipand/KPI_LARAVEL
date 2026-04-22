<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Summary Report — {{ $period }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { background: #1e293b; color: white; padding: 20px 24px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p  { font-size: 11px; margin-top: 4px; opacity: .8; }
        .stats { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; }
        .stat { flex: 1; padding: 14px 20px; border-right: 1px solid #e2e8f0; }
        .stat label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; display: block; }
        .stat span  { font-size: 22px; font-weight: 800; color: #1e293b; }
        .stat.accent span { color: #2563EB; }
        .section { padding: 16px 24px; }
        .section h2 { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 10px; padding-bottom: 4px; border-bottom: 2px solid #2563EB; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e293b; color: white; text-align: left; padding: 7px 10px; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 32px; padding: 12px 24px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; }
        .bar { background: #2563EB; height: 8px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="header">
    <h1>KPI Performance Summary Report</h1>
    <p>Period: {{ $period }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}</p>
</div>

<div class="stats">
    <div class="stat">
        <label>Total Assignments</label>
        <span>{{ $total_assignments }}</span>
    </div>
    <div class="stat accent">
        <label>Approved</label>
        <span>{{ $approved }}</span>
    </div>
    <div class="stat">
        <label>Pending / Review</label>
        <span>{{ $pending }}</span>
    </div>
    <div class="stat accent">
        <label>Average Score</label>
        <span>{{ number_format($avg_score, 1) }}%</span>
    </div>
    <div class="stat">
        <label>Highest Score</label>
        <span>{{ number_format($highest_score, 1) }}%</span>
    </div>
    <div class="stat">
        <label>Lowest Score</label>
        <span>{{ number_format($lowest_score, 1) }}%</span>
    </div>
</div>

<div class="section">
    <h2>Department Performance Breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Employees</th>
                <th>Avg Score</th>
                <th>Highest</th>
                <th>Lowest</th>
                <th style="width:120px">Score Bar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($department_breakdown as $dept)
            <tr>
                <td>{{ $dept['department'] }}</td>
                <td>{{ $dept['total_emp'] }}</td>
                <td>{{ number_format($dept['avg_score'], 1) }}%</td>
                <td>{{ number_format($dept['max_score'], 1) }}%</td>
                <td>{{ number_format($dept['min_score'], 1) }}%</td>
                <td>
                    <div class="bar-wrap">
                        <div class="bar" style="width: {{ min($dept['avg_score'], 100) }}%;"></div>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:#94a3b8;">No approved data for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    This document is system-generated. &copy; {{ date('Y') }} HR KPI Management System.
</div>

</body>
</html>
