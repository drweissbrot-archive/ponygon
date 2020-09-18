import axios from 'axios'
import store from '~Store'

const refreshData = async () => {
	const lobbyId = store.getters['lobby/id']
	if (! lobbyId) return

	try {
		const { data: { data: lobby } } = await axios.get(`/api/lobby/${lobbyId}`)

		store.dispatch('lobby/setLobby', lobby)

		if (lobby.match) {
			store.dispatch('match/setMatch', lobby.match)
			// TODO refresh match data
		} else {
			store.dispatch('match/clear')
		}
	} catch (e) {
		if (e.response && [401, 403, 404].includes(e.response.status))
		throw e
	}
}

refreshData()
