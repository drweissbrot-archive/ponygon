import echo from '~Echo'
import store from '~Store'

const listenToPlayer = (playerId) => {
	echo.private(`player.${playerId}`)
		.listen('Player\\MatchStarting', ({ match, data }) => {
			store.dispatch('match/setMatch', { match, data })
		})
		.listen('Player\\MatchData', (data) => {
			store.dispatch('match/setData', data)
		})
}

store.subscribe(({ type: mutation }, { player }) => {
	if (mutation !== 'player/setId') return

	listenToPlayer(player.id)
})

if (store.getters['player/id']) {
	listenToPlayer(store.getters['player/id'])
}
