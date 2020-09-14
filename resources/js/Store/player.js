import axios from 'axios'

export default {
	namespaced: true,

	state: {
		id: null,
		name: null,
		previouslySelectedName: null,
		ready: false,
	},

	getters: {
		id(state) {
			return state.id
		},

		name(state) {
			return state.name
		},

		previouslySelectedName(state) {
			return state.previouslySelectedName
		},

		ready(state) {
			return state.ready
		},
	},

	mutations: {
		setId(state, id) {
			state.id = id
		},

		setName(state, name) {
			state.name = name
		},

		setPreviouslySelectedName(state, previouslySelectedName) {
			state.previouslySelectedName = previouslySelectedName
		},

		setReady(state, ready) {
			state.ready = ready
		},
	},

	actions: {
		setPlayer({ commit }, { id, name }) {
			commit('setId', id)
			commit('setName', name)
		},

		setName({ commit }, name) {
			commit('setName', name)
		},

		setPreviouslySelectedName({ commit }, previouslySelectedName) {
			commit('setPreviouslySelectedName', previouslySelectedName)
		},

		async toggleReady({ commit, state }, ready) {
			ready = ! state.ready

			await axios.put('/api/ready', { ready })

			commit('setReady', ready)
		},
	},
}
