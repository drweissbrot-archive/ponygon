<template>
	<main class="page-lobby">
		<h1>Lobby</h1>

		<div class="members">
			<div class="players">
				<Member v-for="member in members" :key="member.id" :member="member" />
			</div>

			<a href="/logout" class="leave" @click.prevent="leave">
				Leave Lobby
			</a>
		</div>

		<div class="invite-link">
			Invite your friends to this lobby with this link:
			<a :href="inviteUrl" @click.prevent="copyInviteLinkToClipboard">
				<code ref="inviteLink">{{ inviteUrl }}</code>
			</a>
		</div>

		<Log :entries="log" />

		<Ready />

		<GameSelector
			v-if="gameConfig.selected_game === null"
			:games="games"
			@updateGameConfig="updateGameConfig"
		/>

		<GameConfig
			v-else
			:games="games"
			@updateGameConfig="updateGameConfig"
		/>
	</main>
</template>

<script>
import axios from 'axios'
import { mapActions, mapGetters } from 'vuex'
import GameConfig from '~Components/Lobby/GameConfig'
import GameSelector from '~Components/Lobby/GameSelector'
import Log from '~Components/Log/Log'
import Member from '~Components/Lobby/Member'
import Ready from '~Components/Lobby/Ready'

export default {
	components: {
		GameSelector,
		GameConfig,
		Log,
		Member,
		Ready,
	},

	data() {
		return {
			games: {
				tictactoe: {
					name: 'Tic Tac Toe',
					players: '2 players',
				},

				werewolves: {
					name: 'Werewolves',
					players: '2+ players, 6+ players recommended',
				},
			},
		}
	},

	methods: {
		...mapActions({
			clearLobby: 'lobby/clear',
		}),

		updateGameConfig({ key, value }) {
			axios.patch(`/api/lobby/${this.lobbyId}/game-config`, { [key]: value })
		},

		async leave() {
			await axios.post('/logout')

			this.clearLobby()
		},

		copyInviteLinkToClipboard() {
			const selection = window.getSelection()
			const range = document.createRange()

			range.selectNodeContents(this.$refs.inviteLink)
			selection.removeAllRanges()
			selection.addRange(range)

			document.execCommand('copy')

			selection.removeAllRanges()

			this.$refs.inviteLink.classList.add('copied-to-clipboard')

			setTimeout(() => {
				this.$refs.inviteLink.classList.remove('copied-to-clipboard')
			}, 1000)
		},
	},

	computed: {
		...mapGetters({
			gameConfig: 'lobby/gameConfig',
			inviteUrl: 'lobby/inviteUrl',
			isLeader: 'playerIsLeader',
			leader: 'lobby/leader',
			lobbyId: 'lobby/id',
			log: 'lobby/log',
			members: 'lobby/members',
			playerId: 'player/id',
		}),
	},
}
</script>
