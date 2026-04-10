export const monthOptions = Array.from({ length: 12 }, (_, index) => ({
    value: index + 1,
    label: new Date(2000, index, 1).toLocaleDateString('id-ID', { month: 'long' }),
}));

export const periodTypeOptions = [
    { value: 'daily', label: 'Harian' },
    { value: 'weekly', label: 'Mingguan' },
    { value: 'monthly', label: 'Bulanan' },
];

export const reportStatusOptions = [
    { value: '', label: 'Semua status' },
    { value: 'draft', label: 'Draft' },
    { value: 'submitted', label: 'Menunggu review' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
];

export const scoreLabelOptions = [
    { value: '', label: 'Semua kategori' },
    { value: 'excellent', label: 'Sangat Baik' },
    { value: 'good', label: 'Baik' },
    { value: 'average', label: 'Cukup' },
    { value: 'bad', label: 'Perlu Perbaikan' },
];
