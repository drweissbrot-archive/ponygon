import echo from '~Echo'
import store from '~Store'

const listenToLobby = (lobbyId) => {
	echo.private(`lobby.${lobbyId}`)
		.listen('Lobby\\PlayerJoined', ({ player }) => {
			store.dispatch('lobby/addMember', player)
		})
		.listen('Lobby\\PlayerLeft', ({ player }) => {
			store.dispatch('lobby/removeMember', player)
		})
		.listen('Lobby\\PlayerKicked', ({ player }) => {
			store.dispatch('lobby/log', `${player.name} was kicked`)
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
}

store.subscribe(({ type: mutation }, { lobby }) => {
	if (mutation !== 'lobby/setId') return

	listenToLobby(lobby.id)
})

if (store.getters['lobby/id']) {
	listenToLobby(store.getters['lobby/id'])

	window.location.hash = `#${store.getters['lobby/id']}`
}
