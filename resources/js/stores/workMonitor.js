import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useWorkMonitorStore = defineStore('work-monitor', () => {
    const kpiTasks    = ref([]);
    const nonKpiTasks = ref([]);
    const kpiPag      = reactive({ total: 0, currentPage: 1, lastPage: 1 });
    const nonKpiPag   = reactive({ total: 0, currentPage: 1, lastPage: 1 });
    const loadingKpi    = ref(false);
    const loadingNonKpi = ref(false);

    function _applyPag(target, pg = {}) {
        target.total       = pg.total        ?? 0;
        target.currentPage = pg.current_page ?? 1;
        target.lastPage    = pg.last_page    ?? 1;
    }

    async function fetchKpiTasks(params = {}) {
        loadingKpi.value = true;
        try {
            const { data: resp } = await api.get('/tasks', {
                params: { ...params, is_kpi: 1, per_page: 50 },
            });
            kpiTasks.value = resp.data?.items || [];
            _applyPag(kpiPag, resp.data?.pagination);
        } finally {
            loadingKpi.value = false;
        }
    }

    async function fetchNonKpiTasks(params = {}) {
        loadingNonKpi.value = true;
        try {
            const { data: resp } = await api.get('/tasks/non-kpi', {
                params: { ...params, per_page: 50 },
            });
            nonKpiTasks.value = resp.data?.items || [];
            _applyPag(nonKpiPag, resp.data?.pagination);
        } finally {
            loadingNonKpi.value = false;
        }
    }

    async function submitReview(taskId, payload) {
        const { data: resp } = await api.patch(`/tasks/${taskId}/non-kpi-review`, payload);
        const idx = nonKpiTasks.value.findIndex((t) => t.id === taskId);
        if (idx !== -1) nonKpiTasks.value[idx] = { ...nonKpiTasks.value[idx], ...resp.data };
        return resp.data;
    }

    return {
        kpiTasks, nonKpiTasks,
        kpiPag, nonKpiPag,
        loadingKpi, loadingNonKpi,
        fetchKpiTasks, fetchNonKpiTasks, submitReview,
    };
});
