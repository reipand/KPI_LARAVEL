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
        const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
        const reverbHost = import.meta.env.VITE_REVERB_HOST;

        const echoOptions = {
            broadcaster,
            key: appKey,
            cluster,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: token ? { Authorization: `Bearer ${token}` } : {},
            },
        };

        // Only set custom wsHost/wsPort when using Reverb (self-hosted).
        // For Pusher hosted, let the SDK resolve the host from the cluster.
        if (reverbHost) {
            echoOptions.wsHost = reverbHost;
            echoOptions.wsPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
            echoOptions.wssPort = Number(import.meta.env.VITE_REVERB_PORT ?? 443);
            echoOptions.forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
        }

        window.Echo = new Echo(echoOptions);
    }
} catch {
    // Echo is optional. Dashboard keeps running with polling when websocket is unavailable.
}
