import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Adds automatic background refresh to any page.
 *
 * Features:
 * - Polling every `interval` ms (default 30 s)
 * - Refresh when the browser tab becomes visible again
 * - Refresh when the window regains focus
 * - Exposes `lastUpdated` (Date ref) and manual `refresh()`
 *
 * @param {Function} callback  Async function that fetches data
 * @param {Object}   options
 * @param {number}   options.interval   Poll interval in ms (default 30000)
 */
export function useAutoRefresh(callback, options = {}) {
    const { interval = 30_000 } = options;

    const lastUpdated = ref(null);
    const isRefreshing = ref(false);
    let timer = null;

    async function refresh() {
        if (isRefreshing.value) return;
        isRefreshing.value = true;
        try {
            await callback();
            lastUpdated.value = new Date();
        } finally {
            isRefreshing.value = false;
        }
    }

    function onVisibilityChange() {
        if (!document.hidden) refresh();
    }

    function onWindowFocus() {
        refresh();
    }

    onMounted(() => {
        timer = setInterval(refresh, interval);
        document.addEventListener('visibilitychange', onVisibilityChange);
        window.addEventListener('focus', onWindowFocus);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
        document.removeEventListener('visibilitychange', onVisibilityChange);
        window.removeEventListener('focus', onWindowFocus);
    });

    return { refresh, lastUpdated, isRefreshing };
}

/**
 * Format a Date as "HH:MM:SS" for "last updated" display.
 */
export function formatTime(date) {
    if (!date) return null;
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
