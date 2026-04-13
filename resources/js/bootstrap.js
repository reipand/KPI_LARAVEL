import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

try {
    const broadcaster = import.meta.env.VITE_BROADCAST_DRIVER ?? 'pusher';
    const appKey = import.meta.env.VITE_PUSHER_APP_KEY ?? '';
    const token = localStorage.getItem('token') ?? '';

    if (appKey) {
        window.Echo = new Echo({
            broadcaster,
            key: appKey,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: token ? { Authorization: `Bearer ${token}` } : {},
            },
        });
    }
} catch {
    // Echo is optional. Dashboard keeps running with polling when websocket is unavailable.
}
