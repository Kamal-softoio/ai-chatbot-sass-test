import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Debug logging for WebSocket configuration
console.log('WebSocket Configuration:');
console.log('VITE_REVERB_APP_KEY:', import.meta.env.VITE_REVERB_APP_KEY);
console.log('VITE_REVERB_HOST:', import.meta.env.VITE_REVERB_HOST);
console.log('VITE_REVERB_PORT:', import.meta.env.VITE_REVERB_PORT);
console.log('VITE_REVERB_SCHEME:', import.meta.env.VITE_REVERB_SCHEME);

try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    console.log('✅ Laravel Echo initialized successfully');

    // Debug connection status - with safety checks
    setTimeout(() => {
        if (window.Echo && window.Echo.connector && window.Echo.connector.socket) {
            window.Echo.connector.socket.on('connect', () => {
                console.log('✅ WebSocket connected successfully to:', import.meta.env.VITE_REVERB_HOST + ':' + import.meta.env.VITE_REVERB_PORT);
            });

            window.Echo.connector.socket.on('disconnect', () => {
                console.log('❌ WebSocket disconnected');
            });

            window.Echo.connector.socket.on('error', (error) => {
                console.error('❌ WebSocket connection error:', error);
            });
        } else {
            console.warn('⚠️ WebSocket connector not available for debugging');
        }
    }, 100);

} catch (error) {
    console.error('❌ Failed to initialize Laravel Echo:', error);
    window.Echo = null;
}
