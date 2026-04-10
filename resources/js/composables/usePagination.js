import { computed, reactive } from 'vue';

const DEFAULT_STATE = {
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
};

export function usePagination(initialState = {}) {
    const state = reactive({
        ...DEFAULT_STATE,
        ...initialState,
    });

    const hasNextPage = computed(() => state.currentPage < state.lastPage);
    const hasPreviousPage = computed(() => state.currentPage > 1);
    const from = computed(() => (state.total === 0 ? 0 : ((state.currentPage - 1) * state.perPage) + 1));
    const to = computed(() => Math.min(state.currentPage * state.perPage, state.total));

    function sync(meta = {}) {
        state.currentPage = Number(meta.current_page ?? meta.currentPage ?? 1);
        state.lastPage = Number(meta.last_page ?? meta.lastPage ?? 1);
        state.perPage = Number(meta.per_page ?? meta.perPage ?? state.perPage);
        state.total = Number(meta.total ?? 0);
    }

    function setPage(page) {
        state.currentPage = Math.max(1, Number(page || 1));
    }

    function setPerPage(perPage) {
        state.perPage = Number(perPage || state.perPage);
        state.currentPage = 1;
    }

    function reset() {
        Object.assign(state, DEFAULT_STATE, initialState);
    }

    return {
        state,
        hasNextPage,
        hasPreviousPage,
        from,
        to,
        sync,
        setPage,
        setPerPage,
        reset,
    };
}
