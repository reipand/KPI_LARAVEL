export function formatDate(value, options = {}) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...options,
    });
}

export function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function buildPeriodLabel(periodType, tanggal) {
    const date = new Date(tanggal || new Date());

    if (periodType === 'monthly') {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    }

    if (periodType === 'weekly') {
        const current = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNumber = current.getUTCDay() || 7;
        current.setUTCDate(current.getUTCDate() + 4 - dayNumber);
        const yearStart = new Date(Date.UTC(current.getUTCFullYear(), 0, 1));
        const weekNumber = Math.ceil((((current - yearStart) / 86400000) + 1) / 7);

        return `${current.getUTCFullYear()}-W${String(weekNumber).padStart(2, '0')}`;
    }

    return date.toISOString().slice(0, 10);
}

export function statusLabel(status) {
    return {
        draft: 'Draft',
        submitted: 'Menunggu review',
        approved: 'Disetujui',
        rejected: 'Ditolak',
    }[status] ?? status ?? '-';
}

export function statusClass(status) {
    return {
        draft: 'border-slate-200 bg-slate-100 text-slate-700',
        submitted: 'border-amber-200 bg-amber-50 text-amber-700',
        approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-200 bg-rose-50 text-rose-700',
    }[status] ?? 'border-slate-200 bg-slate-100 text-slate-700';
}

export function scoreLabel(label) {
    return {
        excellent: 'Sangat Baik',
        good: 'Baik',
        average: 'Cukup',
        bad: 'Perlu Perbaikan',
    }[label] ?? '-';
}

export function scoreClass(label) {
    return {
        excellent: 'border-cyan-200 bg-cyan-50 text-cyan-700',
        good: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        average: 'border-amber-200 bg-amber-50 text-amber-700',
        bad: 'border-rose-200 bg-rose-50 text-rose-700',
    }[label] ?? 'border-slate-200 bg-slate-100 text-slate-700';
}

export function progressTone(value) {
    const percentage = Number(value);

    if (!Number.isFinite(percentage)) {
        return {
            bar: 'bg-slate-300',
            text: 'text-slate-500',
            surface: 'bg-slate-100',
        };
    }

    if (percentage >= 100) {
        return {
            bar: 'bg-emerald-500',
            text: 'text-emerald-700',
            surface: 'bg-emerald-50',
        };
    }

    if (percentage >= 80) {
        return {
            bar: 'bg-sky-500',
            text: 'text-sky-700',
            surface: 'bg-sky-50',
        };
    }

    if (percentage >= 50) {
        return {
            bar: 'bg-amber-500',
            text: 'text-amber-700',
            surface: 'bg-amber-50',
        };
    }

    return {
        bar: 'bg-rose-500',
        text: 'text-rose-700',
        surface: 'bg-rose-50',
    };
}

export function formatPercentage(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '-';
    }

    return `${Math.round(Number(value))}%`;
}
