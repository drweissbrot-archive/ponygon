import createPersistedState from 'vuex-persistedstate'
import Vue from 'vue'
import Vuex from 'vuex'
import lobby from './lobby'
import match from './match'
import player from './player'

Vue.use(Vuex)

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',

	plugins: [
		createPersistedState(),
	],

	modules: {
		player,
		match,
		lobby,
	},

	getters: {
		playerIsLeader(_, getters) {
			const leaderId = getters['lobby/leaderId']

			return leaderId && leaderId === getters['player/id']
		},
	},

	actions: {
		clear({ dispatch }) {
			dispatch('match/clear')
			dispatch('lobby/clear')
			dispatch('player/clear')
		},
	},
})
