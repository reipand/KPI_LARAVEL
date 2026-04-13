import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/api';

function getMonthStart(value) {
    const date = value ? new Date(`${value}-01T00:00:00`) : new Date();

    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function formatMonthValue(date) {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');

    return `${year}-${month}`;
}

function subtractMonths(date, count) {
    return new Date(date.getFullYear(), date.getMonth() - count, 1);
}

export const useKpiDashboardStore = defineStore('kpi-dashboard', () => {
    const dashboard = ref(null);
    const trend = ref([]);
    const detail = ref(null);

    const isLoadingDashboard = ref(false);
    const isLoadingTrend = ref(false);
    const isLoadingDetail = ref(false);

    const filters = ref({
        period: formatMonthValue(new Date()),
        roleId: '',
        employeeId: '',
    });

    const ranking = computed(() => dashboard.value?.ranking ?? []);
    const summary = computed(() => dashboard.value?.summary ?? null);

    function buildDashboardParams(overrides = {}) {
        const merged = {
            period: filters.value.period,
            roleId: filters.value.roleId,
            employeeId: filters.value.employeeId,
            ...overrides,
        };

        const periodDate = getMonthStart(merged.period);

        return {
            period_type: 'monthly',
            period: periodDate.toISOString().slice(0, 10),
            role_id: merged.roleId || undefined,
            employee_id: merged.employeeId || undefined,
        };
    }

    async function fetchDashboard(overrides = {}) {
        isLoadingDashboard.value = true;

        try {
            const params = buildDashboardParams(overrides);
            const { data: response } = await api.get('/kpi/dashboard', { params });
            dashboard.value = response.data;
        } finally {
            isLoadingDashboard.value = false;
        }
    }

    async function fetchTrend(overrides = {}) {
        isLoadingTrend.value = true;

        try {
            const current = getMonthStart(overrides.period ?? filters.value.period);
            const requests = Array.from({ length: 6 }, (_, index) => {
                const period = subtractMonths(current, 5 - index);

                return api.get('/kpi/dashboard', {
                    params: buildDashboardParams({
                        ...overrides,
                        period: formatMonthValue(period),
                    }),
                });
            });

            const responses = await Promise.all(requests);

            trend.value = responses.map((response, index) => {
                const pointDate = subtractMonths(current, 5 - index);

                return {
                    label: pointDate.toLocaleDateString('id-ID', { month: 'short' }),
                    average: response.data?.data?.summary?.average_kpi ?? 0,
                    employees: response.data?.data?.summary?.employee_count ?? 0,
                };
            });
        } finally {
            isLoadingTrend.value = false;
        }
    }

    async function fetchUserDetail(userId, overrides = {}) {
        if (!userId) {
            detail.value = null;
            return null;
        }

        isLoadingDetail.value = true;

        try {
            const params = buildDashboardParams(overrides);
            const { data: response } = await api.get(`/kpi/user/${userId}`, { params });
            detail.value = response.data;

            return detail.value;
        } finally {
            isLoadingDetail.value = false;
        }
    }

    async function hydrate(overrides = {}) {
        await Promise.all([
            fetchDashboard(overrides),
            fetchTrend(overrides),
        ]);
    }

    function setFilter(key, value) {
        filters.value = {
            ...filters.value,
            [key]: value,
        };
    }

    return {
        dashboard,
        trend,
        detail,
        filters,
        ranking,
        summary,
        isLoadingDashboard,
        isLoadingTrend,
        isLoadingDetail,
        fetchDashboard,
        fetchTrend,
        fetchUserDetail,
        hydrate,
        setFilter,
    };
});
