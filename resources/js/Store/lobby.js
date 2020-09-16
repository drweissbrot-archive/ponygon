export default {
	namespaced: true,

	state: {
		id: null,
		leaderId: null,
		inviteUrl: null,

		members: [],
		gameConfig: {},
		log: [],
	},

	getters: {
		id(state) {
			return state.id
		},

		leader(state, getters) {
			return getters.memberById(state.leaderId)
		},

		leaderId(state) {
			return state.leaderId
		},

		inviteUrl(state) {
			return state.inviteUrl
		},

		members(state) {
			return state.members
		},

		memberById(state) {
			return (needle) => {
				return state.members.find(({ id }) => id === needle)
			}
		},

		gameConfig(state) {
			return state.gameConfig
		},

		log(state) {
			return state.log
		},
	},

	mutations: {
		setId(state, id) {
			state.id = id

			window.location.hash = (id === null) ? '' : `#${id}`
		},

		setLeaderId(state, leaderId) {
			state.leaderId = leaderId
		},

		setInviteUrl(state, inviteUrl) {
			state.inviteUrl = inviteUrl
		},

		setGameConfig(state, gameConfig) {
			state.gameConfig = gameConfig
		},

		addMember(state, member) {
			state.members.push(member)
		},

		removeMember(state, memberId) {
			const index = state.members.findIndex(({ id }) => id === memberId)
			if (index === -1) return

			state.members.splice(index, 1)
		},

		modifyMember(state, { id, property, value }) {
			const index = state.members.findIndex((member) => member.id === id)
			if (index === -1) return

			state.members[index][property] = value
		},

		clearMembers(state) {
			state.members = []
		},

		clearLog(state) {
			state.log = []
		},

		appendToLog(state, entry) {
			state.log.push(entry)
		},
	},

	actions: {
		setLobby({ commit }, { id, leader_id, invite_url, game_config, members }) {
			commit('setId', id)
			commit('setLeaderId', leader_id)
			commit('setInviteUrl', invite_url)
			commit('setGameConfig', game_config)
			commit('clearLog')

			for (const member of members) {
				commit('addMember', member)
			}
		},

		setLeaderId({ commit, dispatch, getters }, leaderId) {
			commit('setLeaderId', leaderId)
			dispatch('log', `${getters.leader.name} was promoted to lobby leader`)
		},

		addMember({ commit, dispatch }, member) {
			commit('addMember', member)
			dispatch('log', `${member.name} joined`)
		},

		removeMember({ commit, dispatch }, member) {
			commit('removeMember', member.id)
			dispatch('log', `${member.name} left`)
		},

		setMemberReady({ commit, dispatch, getters }, { player: id, ready }) {
			commit('modifyMember', { id, property: 'ready', value: ready })

			dispatch('log', `${getters.memberById(id).name} is ` + ((ready) ? 'ready' : 'not ready'))
		},

		setAllUnready({ commit, getters }) {
			for (const player of getters.members) {
				commit('modifyMember', { id: player.id, property: 'ready', value: false })
			}
		},

		mergeGameConfig({ commit, state }, config) {
			commit('setGameConfig', Object.assign({}, state.gameConfig, config))
		},

		log({ commit }, message) {
			let entry = { at: new Date().toJSON() }

			;(typeof message === 'object')
				? entry = Object.assign(entry, message)
				: entry.message = message

			commit('appendToLog', entry)
		},

		clear({ commit }) {
			commit('setId', null)
			commit('setLeaderId', null)
			commit('setInviteUrl', null)
			commit('setGameConfig', null)
			commit('clearMembers')
			commit('clearLog')
		},
	},
}
