<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analytics KPI {{ $tahun }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; margin: 28px; }
        h1, h2, p { margin: 0; }
        h1 { font-size: 22px; color: #0f172a; }
        h2 { font-size: 14px; margin-bottom: 10px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-weight: 700; }
        .header { border-bottom: 2px solid #1e3a5f; margin-bottom: 18px; padding-bottom: 14px; }
        .muted { color: #64748b; }
        .section { margin-top: 18px; }
        .summary td { width: 25%; }
        .value { display: block; margin-top: 4px; font-size: 17px; font-weight: 700; color: #1e3a5f; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company }}</h1>
        <p class="muted">Analytics KPI {{ $periodLabel }} - {{ $departmentLabel }}</p>
        <p class="muted">Generated at {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>Total Karyawan<span class="value">{{ $summary['total_employees'] }}</span></td>
            <td>Departemen Aktif<span class="value">{{ $summary['total_departments'] }}</span></td>
            <td>Total Laporan<span class="value">{{ $summary['total_reports'] }}</span></td>
            <td>Rata-rata Achievement<span class="value">{{ number_format((float) $summary['avg_achievement'], 1) }}%</span></td>
        </tr>
    </table>

    <div class="section">
        <h2>Tren Bulanan</h2>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Rata-rata Achievement</th>
                    <th>Jumlah Laporan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $row)
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        <td>{{ $row['avg_percentage'] === null ? '-' : number_format((float) $row['avg_percentage'], 1) . '%' }}</td>
                        <td>{{ $row['total_reports'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Perbandingan Departemen</h2>
        <table>
            <thead>
                <tr>
                    <th>Departemen</th>
                    <th>Karyawan</th>
                    <th>Achievement KPI</th>
                    <th>Skor KPI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departmentRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['employee_count'] }}</td>
                        <td>{{ $row['avg_percentage'] === null ? '-' : number_format((float) $row['avg_percentage'], 1) . '%' }}</td>
                        <td>{{ $row['avg_task_score'] === null ? '-' : number_format((float) $row['avg_task_score'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Distribusi Laporan KPI</h2>
        <table>
            <thead>
                <tr>
                    @foreach($reportDistribution as $label => $count)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($reportDistribution as $count)
                        <td>{{ $count }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Distribusi Skor KPI Pegawai</h2>
        <table>
            <thead>
                <tr>
                    @foreach($taskDistribution as $label => $count)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($taskDistribution as $count)
                        <td>{{ $count }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top 10 Laporan KPI</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Departemen</th>
                    <th>Indikator</th>
                    <th>Persentase</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topReports as $report)
                    @php
                        $department = $report->user?->getRelation('department');
                    @endphp
                    <tr>
                        <td>{{ $report->user?->nama ?? '-' }}</td>
                        <td>{{ $department?->nama ?? $report->user?->departemen ?? '-' }}</td>
                        <td>{{ $report->kpiIndicator?->name ?? '-' }}</td>
                        <td>{{ $report->persentase === null ? '-' : number_format((float) $report->persentase, 1) . '%' }}</td>
                        <td>{{ $report->tanggal?->format('d M Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada laporan KPI untuk tahun ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dokumen ini digenerate secara otomatis oleh Sistem KPI BASS Training Center &amp; Consultant.
        &copy; {{ now()->year }} BASS Training.
    </div>
</body>
</html>
