import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: "0c4j4jjnudyjtitefyr7",
    wsHost: "192.168.1.187",
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
