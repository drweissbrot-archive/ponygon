import Echo from 'laravel-echo'

window.Pusher = require('pusher-js')

export default new class {
	constructor() {
		this.echo = new Echo({
			broadcaster: 'pusher',
			key: 'ponygon',
			wsHost: window.location.hostname,
			wssPort: 8443,
			forceTLS: true,
			enableStats: false,
			enabledTransports: ['ws', 'wss'],
		})

		this.privateChannels = {}
	}

	private(channel) {
		if (! this.privateChannels[channel]) {
			this.privateChannels[channel] = this.echo.private(channel)
		}

		return this.privateChannels[channel]
	}

	singlePlayer(playerId) {
		for (const channel in this.privateChannels) {
			if (channel.startsWith('player.') && channel !== `player.${playerId}`) this.echo.leave(channel)
		}

		return this.echo.private(`player.${playerId}`)
	}

	singleLobby(lobbyId) {
		for (const channel in this.privateChannels) {
			if (channel.startsWith('lobby.') && channel !== `lobby.${lobbyId}`) this.echo.leave(channel)
		}

		return this.echo.private(`lobby.${lobbyId}`)
	}
}
