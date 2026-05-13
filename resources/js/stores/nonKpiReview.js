import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useNonKpiReviewStore = defineStore('non-kpi-review', () => {
    const tasks = ref([]);
    const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0 });
    const isLoading = ref(false);

    async function fetchTasks(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/tasks/non-kpi', { params });
            tasks.value = resp.data?.items || [];
            const pg = resp.data?.pagination || {};
            pagination.currentPage = pg.current_page ?? 1;
            pagination.lastPage    = pg.last_page    ?? 1;
            pagination.total       = pg.total        ?? 0;
        } finally {
            isLoading.value = false;
        }
    }

    async function submitReview(taskId, payload) {
        const { data: resp } = await api.patch(`/tasks/${taskId}/non-kpi-review`, payload);
        const idx = tasks.value.findIndex((t) => t.id === taskId);
        if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], ...resp.data };
        return resp.data;
    }

    return { tasks, pagination, isLoading, fetchTasks, submitReview };
});
