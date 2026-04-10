import { ref } from 'vue';

export function useApiHandler() {
    const isLoading = ref(false);
    const error = ref('');

    async function execute(request, options = {}) {
        const {
            loadingRef = isLoading,
            onSuccess,
            onError,
            rethrow = true,
        } = options;

        loadingRef.value = true;
        error.value = '';

        try {
            const result = await request();
            onSuccess?.(result);
            return result;
        } catch (err) {
            error.value = err?.userMessage || err?.message || 'Terjadi kesalahan. Coba lagi.';
            onError?.(err);

            if (rethrow) {
                throw err;
            }

            return null;
        } finally {
            loadingRef.value = false;
        }
    }

    function clearError() {
        error.value = '';
    }

    return {
        isLoading,
        error,
        execute,
        clearError,
    };
}
