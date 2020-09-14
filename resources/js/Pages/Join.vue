<template>
	<main class="page-join">
		<h1>
			Welcome to Ponygon
		</h1>

		<form @submit.prevent="submit">
			<div class="input-group">
				<label for="name">
					Please enter your name
				</label>

				<input ref="name" type="text" id="name" minlength="3" maxlength="32" v-model="name" required autofocus>
			</div>

			<div class="input-group">
				<label for="lobby_id">
					Do you want to join an existing Lobby?
					Lobby ID
				</label>

				<input type="text" id="lobby_id" v-model="lobbyId">
			</div>

			<div class="input-group --submit">
				<button type="submit">
					{{ lobbyId ? 'Join Lobby' : 'Create Lobby' }}
				</button>
			</div>
		</form>
	</main>
</template>

<script>
import axios from 'axios'
import { mapActions, mapGetters } from 'vuex'

export default {
	data({ $store }) {
		return {
			name: $store.getters['player/previouslySelectedName'],
			lobbyId: window.location.hash.length === 37
				? window.location.hash.substring(1)
				: null,
		}
	},

	mounted() {
		this.$refs.name.focus()
	},

	methods: {
		...mapActions({
			setLobby: 'lobby/setLobby',
			setPlayer: 'player/setPlayer',
			setPlayerName: 'player/setName',
			setPreviouslySelectedName: 'player/setPreviouslySelectedName',
		}),

		async submit() {
			this.setPreviouslySelectedName(this.name)

			const { data: { data: player } } = await axios.post('/api/player', { name: this.name })
			// TODO validation
			// TODO what happens when user is already signed in, but has not joined a lobby yet?

			this.setPlayer(player)

			const { data } = await axios.post('/api/lobby/' + (this.lobbyId || ''))

			this.setLobby(data.data)

			if (data.hasOwnProperty('replaced_name')) this.setPlayerName(data.replaced_name)
		},
	},

	computed: {
		...mapGetters({
			previouslySelectedName: 'player/previouslySelectedName',
		}),
	},
}
</script>
