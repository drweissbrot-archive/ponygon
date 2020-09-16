export default {
	namespaced: true,

	state: {
		id: null,
		game: null,

		scoreboard: {},
		data: {},
	},

	getters: {
		id(state) {
			return state.id
		},

		game(state) {
			return state.game
		},

		data(state) {
			return state.data
		},

		scoreboardData(state) {
			return state.scoreboard
		},
	},

	mutations: {
		setId(state, id) {
			state.id = id
		},

		setGame(state, game) {
			state.game = game
		},

		setScoreboardData(state, data) {
			state.scoreboard = data
		},

		setData(state, data) {
			state.data = data
		},
	},

	actions: {
		setMatch({ commit, dispatch }, { match, data }) {
			commit('setId', match.id)
			commit('setGame', match.game)

			dispatch('setData', data)
		},

		setData({ commit }, data) {
			if (data.hasOwnProperty('scoreboard')) {
				commit('setScoreboardData', data.scoreboard)
				delete data.scoreboard
			}

			commit('setData', data)
		},

		clear({ commit }) {
			commit('setId', null)
			commit('setGame', null)
			commit('setData', null)
		},
	},
}
