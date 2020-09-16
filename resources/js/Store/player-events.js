import echo from '~Echo'
import store from '~Store'

const listenToPlayer = (playerId) => {
	echo.private(`player.${playerId}`)
		.listen('Player\\MatchStarting', ({ match, data }) => {
			// TODO
		})
}

store.subscribe(({ type: mutation }, { player }) => {
	if (mutation !== 'player/setId') return

	listenToPlayer(player.id)
})

if (store.getters['player/id']) {
	listenToPlayer(store.getters['player/id'])
}
