import Echo from 'laravel-echo'

window.Pusher = require('pusher-js')

export default new Echo({
	broadcaster: 'pusher',
	key: 'ponygon',
	wsHost: window.location.hostname,
	wssPort: 8443,
	forceTLS: true,
	enableStats: false,
	enabledTransports: ['ws', 'wss'],
})
