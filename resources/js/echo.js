import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

try {
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    if (reverbKey) {
        const isHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
        const wsHost = import.meta.env.VITE_REVERB_HOST || (typeof window !== 'undefined' ? window.location.hostname : 'localhost');
        const wsPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : (isHttps ? 443 : 80);
        const wssPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 443;
        const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? (isHttps ? 'https' : 'http')) === 'https';

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: wsHost,
            wsPort: isHttps ? wssPort : wsPort,
            wssPort: wssPort,
            forceTLS: forceTLS,
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel, options) => {
                return {
                    authorize: (socketId, callback) => {
                        fetch('/broadcasting/auth', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                socket_id: socketId,
                                channel_name: channel.name,
                            }),
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Auth failed with status ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => callback(null, data))
                        .catch(error => {
                            console.warn('[Echo] Error autorizando canal privado:', error);
                            callback(error);
                        });
                    },
                };
            },
        });
    }
} catch (error) {
    console.warn('[Echo] No se pudo inicializar Laravel Echo:', error);
}
