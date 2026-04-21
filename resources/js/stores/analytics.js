import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useAnalyticsStore = defineStore('analytics', () => {
    const trend        = ref(null);
    const perDepartment = ref(null);
    const distribution = ref(null);
    const overview     = ref(null);

    const isLoadingTrend        = ref(false);
    const isLoadingDepartment   = ref(false);
    const isLoadingDistribution = ref(false);
    const isLoadingOverview     = ref(false);

    const filters = reactive({
        tahun: new Date().getFullYear(),
        bulan: null,
        department_id: null,
    });

    async function fetchTrend(params = {}) {
        isLoadingTrend.value = true;
        try {
            const { data: resp } = await api.get('/analytics/trend', {
                params: {
                    tahun: filters.tahun,
                    department_id: filters.department_id || undefined,
                    ...params,
                },
            });
            trend.value = resp.data;
        } finally {
            isLoadingTrend.value = false;
        }
    }

    async function fetchPerDepartment(params = {}) {
        isLoadingDepartment.value = true;
        try {
            const { data: resp } = await api.get('/analytics/per-department', {
                params: {
                    tahun: filters.tahun,
                    bulan: filters.bulan || undefined,
                    department_id: filters.department_id || undefined,
                    ...params,
                },
            });
            perDepartment.value = resp.data;
        } finally {
            isLoadingDepartment.value = false;
        }
    }

    async function fetchDistribution(params = {}) {
        isLoadingDistribution.value = true;
        try {
            const { data: resp } = await api.get('/analytics/distribution', {
                params: {
                    tahun: filters.tahun,
                    bulan: filters.bulan || undefined,
                    department_id: filters.department_id || undefined,
                    ...params,
                },
            });
            distribution.value = resp.data;
        } finally {
            isLoadingDistribution.value = false;
        }
    }

    async function fetchOverview(params = {}) {
        isLoadingOverview.value = true;
        try {
            const { data: resp } = await api.get('/analytics/overview', {
                params: {
                    tahun: filters.tahun,
                    bulan: filters.bulan || undefined,
                    department_id: filters.department_id || undefined,
                    ...params,
                },
            });
            overview.value = resp.data;
        } finally {
            isLoadingOverview.value = false;
        }
    }

    async function fetchAll() {
        await Promise.all([
            fetchTrend(),
            fetchPerDepartment(),
            fetchDistribution(),
            fetchOverview(),
        ]);
    }

    function setFilter(key, value) {
        filters[key] = value;
    }

    return {
        trend,
        perDepartment,
        distribution,
        overview,
        filters,
        isLoadingTrend,
        isLoadingDepartment,
        isLoadingDistribution,
        isLoadingOverview,
        fetchTrend,
        fetchPerDepartment,
        fetchDistribution,
        fetchOverview,
        fetchAll,
        setFilter,
    };
});
