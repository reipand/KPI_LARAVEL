import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useTaskStore = defineStore('task', () => {
    const tasks = ref([]);
    const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0, perPage: 15 });
    const isLoading = ref(false);
    const filters = reactive({
        bulan: new Date().getMonth() + 1,
        tahun: new Date().getFullYear(),
        status: '',
    });

    async function fetchTasks(params = {}) {
        isLoading.value = true;
        tasks.value = [];
        try {
            // Response: { success, data: { items: [...], pagination: {...} }, message }
            const { data: resp } = await api.get('/tasks', {
                params: { ...filters, ...params, page: pagination.currentPage },
            });

            tasks.value = resp.data?.items || [];
            const pg = resp.data?.pagination || {};
            pagination.currentPage = pg.current_page ?? 1;
            pagination.lastPage = pg.last_page ?? 1;
            pagination.total = pg.total ?? 0;
            pagination.perPage = pg.per_page ?? 15;
        } finally {
            isLoading.value = false;
        }
    }

    async function createTask(payload) {
        const isFormData = payload instanceof FormData;
        const { data: resp } = await api.post('/tasks', payload, isFormData ? {
            headers: { 'Content-Type': 'multipart/form-data' },
        } : {});
        return resp.data;
    }

    async function updateTask(id, payload) {
        const isFormData = payload instanceof FormData;
        if (isFormData) {
            // Laravel tidak support PUT dengan multipart; gunakan POST + _method
            payload.append('_method', 'PUT');
            const { data: resp } = await api.post(`/tasks/${id}`, payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return resp.data;
        }
        const { data: resp } = await api.put(`/tasks/${id}`, payload);
        return resp.data;
    }

    async function deleteTask(id) {
        await api.delete(`/tasks/${id}`);
        tasks.value = tasks.value.filter((t) => t.id !== id);
    }

    async function mapKpi(taskId, payload) {
        const { data: resp } = await api.put(`/tasks/${taskId}/mapping`, payload);
        return resp.data;
    }

    function setPage(page) {
        pagination.currentPage = page;
    }

    return { tasks, pagination, isLoading, filters, fetchTasks, createTask, updateTask, deleteTask, mapKpi, setPage };
});
