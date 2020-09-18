import echo from '~Echo'
import store from '~Store'

const listenToLobby = async (lobbyId) => {
	echo.private(`lobby.${lobbyId}`)
		.listen('Lobby\\PlayerJoined', ({ player }) => {
			store.dispatch('lobby/addMember', player)
		})
		.listen('Lobby\\PlayerLeft', ({ player }) => {
			store.dispatch('lobby/removeMember', player)
		})
		.listen('Lobby\\PlayerKicked', ({ player }) => {
			store.dispatch('lobby/log', `${player.name} was kicked`)

			if (player.id === store.getters['player/id']) store.dispatch('clear')
		})
		.listen('Lobby\\PlayerSetReady', (e) => {
			store.dispatch('lobby/setMemberReady', e)
		})
		.listen('Lobby\\PlayerPromotedToLeader', (e) => {
			store.dispatch('lobby/setLeaderId', e.player.id)
		})
		.listen('Lobby\\GameConfigChanged', (e) => {
			store.dispatch('lobby/mergeGameConfig', e)
		})
		.listen('Lobby\\MatchCancelled', () => {
			store.dispatch('match/clear')
		})
		.listen('Lobby\\MatchEnded', () => {
			store.dispatch('player/setReady', false)
			store.dispatch('lobby/setAllUnready')
		})
}

store.subscribe(({ type: mutation }, { lobby }) => {
	if (mutation !== 'lobby/setId') return

	listenToLobby(lobby.id)
})

const lobbyId = store.getters['lobby/id']

if (lobbyId) {
	listenToLobby(lobbyId)
	window.location.hash = `#${store.getters['lobby/id']}`
}
