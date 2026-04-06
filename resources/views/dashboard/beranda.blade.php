@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<section class="page-hero">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
            <div class="page-hero-meta">Ringkasan Kinerja</div>
            <h2 class="mt-4 text-3xl font-bold leading-tight">Pantau performa kerja, keterlambatan, dan capaian KPI dalam satu tampilan.</h2>
            <p class="mt-3 text-sm leading-7 text-white/78">
                Dashboard ini merangkum progres pekerjaan, kualitas penyelesaian, dan nilai KPI personal agar evaluasi lebih cepat dan terukur.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/12 bg-white/10 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Periode</div>
                <div class="mt-2 text-lg font-bold">{{ now()->translatedFormat('F Y') }}</div>
            </div>
            <div class="rounded-2xl border border-white/12 bg-white/10 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Predikat</div>
                <div class="mt-2 text-lg font-bold">{{ $kpi->predikat }}</div>
            </div>
            <div class="col-span-2 rounded-2xl border border-white/12 bg-white/10 px-4 py-4 sm:col-span-1">
                <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Nilai</div>
                <div class="mt-2 text-lg font-bold">{{ number_format($kpi->total, 2) }}/5.00</div>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    <div class="stat-card">
        <div class="stat-label">Total Pekerjaan</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold text-slate-900">{{ $tasks->count() }}</div>
            <div class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">Semua status</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pekerjaan Selesai</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold text-green-700">{{ $tasks->where('status', 'Selesai')->count() }}</div>
            <div class="badge-success">Tuntas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nilai KPI</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold text-slate-900">{{ number_format($kpi->total, 2) }}</div>
            <div class="badge-info">{{ $kpi->predikat }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Delay</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold {{ $tasks->where('has_delay', true)->count() > 0 ? 'text-red-700' : 'text-green-700' }}">
                {{ $tasks->where('has_delay', true)->count() }}
            </div>
            <div class="{{ $tasks->where('has_delay', true)->count() > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $tasks->where('has_delay', true)->count() > 0 ? 'Perlu perhatian' : 'Aman' }}
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Error</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold {{ $tasks->where('has_error', true)->count() > 0 ? 'text-red-700' : 'text-green-700' }}">
                {{ $tasks->where('has_error', true)->count() }}
            </div>
            <div class="{{ $tasks->where('has_error', true)->count() > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $tasks->where('has_error', true)->count() > 0 ? 'Perlu koreksi' : 'Stabil' }}
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Komplain</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="text-4xl font-bold {{ $tasks->where('has_complaint', true)->count() > 0 ? 'text-red-700' : 'text-green-700' }}">
                {{ $tasks->where('has_complaint', true)->count() }}
            </div>
            <div class="{{ $tasks->where('has_complaint', true)->count() > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $tasks->where('has_complaint', true)->count() > 0 ? 'Ditindaklanjuti' : 'Terkendali' }}
            </div>
        </div>
    </div>
</section>

<section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_0.8fr]">
    <div class="card">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="stat-label">Status KPI</p>
                <h3 class="mt-2 text-xl font-bold text-slate-900">Ringkasan KPI Anda</h3>
            </div>
            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-right">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($kpi->total, 2) }}</div>
                <div class="text-xs text-slate-500">Dari 5.00</div>
            </div>
        </div>

        <div class="mb-6 rounded-[22px] border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">{{ session('user')->position }}</div>
                    <div class="mt-1 text-sm text-slate-500">Predikat saat ini: {{ $kpi->predikat }}</div>
                </div>
                <div class="w-full max-w-md">
                    <div class="mb-2 flex items-center justify-between text-xs text-slate-500">
                        <span>Progress KPI</span>
                        <span>{{ number_format(($kpi->total / 5) * 100, 0) }}%</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-slate-200">
                        <div class="h-2.5 rounded-full bg-[linear-gradient(90deg,#1a3a5c,#2563a8)]" style="width: {{ min(100, ($kpi->total / 5) * 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            @foreach($kpi->components as $comp)
                <div class="data-row">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">{{ $comp->component->objective }}</div>
                        <div class="mt-1 text-xs text-slate-500">Bobot {{ number_format($comp->component->weight * 100, 0) }}%</div>
                    </div>
                    <div class="w-full max-w-xs">
                        <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                            <span>Skor {{ number_format($comp->skor, 2) }}</span>
                            <span>{{ number_format($comp->bobotSkor, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-emerald-600" style="width: {{ ($comp->skor / 5) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-muted">
        <div class="mb-5">
            <p class="stat-label">Aktivitas</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Pekerjaan Terakhir</h3>
        </div>

        @if($recentTasks->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500">
                Belum ada pekerjaan yang diinput.
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentTasks as $task)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $task->title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $task->task_date->format('d/m/Y') }} - {{ $task->type ?? '-' }}</div>
                            </div>
                            <span class="
                                @if($task->status == 'Selesai') badge-success
                                @elseif($task->status == 'Dalam Proses') badge-info
                                @else badge-warning @endif
                            ">
                                {{ $task->status }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="card mt-6">
    <div class="mb-5 flex items-center justify-between gap-4">
        <div>
            <p class="stat-label">Ranking Internal</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Top 3 Performer</h3>
        </div>
        <div class="rounded-2xl bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">Update otomatis dari skor KPI</div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach($rankedEmployees as $rank)
            <div class="rounded-[24px] border p-5 shadow-sm {{ $loop->iteration == 1 ? 'border-amber-200 bg-amber-50/80' : ($loop->iteration == 2 ? 'border-emerald-200 bg-emerald-50/80' : 'border-yellow-200 bg-yellow-50/80') }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Peringkat {{ $loop->iteration }}</div>
                        <div class="mt-3 text-lg font-bold text-slate-900">{{ $rank->employee->name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $rank->employee->position }} - {{ $rank->employee->department }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/80 px-3 py-2 text-right shadow-sm">
                        <div class="text-xl font-bold text-slate-900">{{ number_format($rank->score, 2) }}</div>
                        <div class="text-xs text-slate-500">{{ $rank->predikat }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
